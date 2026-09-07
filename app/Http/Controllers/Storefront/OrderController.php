<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Company;
use App\Models\Order;

use App\Services\Platform\EmailService;
use App\Services\OrderService;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    // ════════════════════════════════════════════════════
    //  PLACE ORDER — POST /{slug}/orders
    //  Called via AJAX from cart drawer checkout panel
    //  Returns JSON — Alpine handles success/error state
    // ════════════════════════════════════════════════════

    /**
     * What the cart would cost if it were ordered now — GST included.
     *
     * The cart lives in the browser and its total was worked out there as
     * price × qty, so a customer was quoted a figure with no tax in it and then
     * charged a different one. Rather than reimplement GST in JavaScript — with
     * inclusive pricing, CGST/SGST splitting and inter-state rules to keep in
     * step — the browser sends SKU ids and quantities and the server answers
     * with the same arithmetic the order itself will use.
     *
     * Only ids and quantities are accepted; prices and tax rates are read from
     * the database, exactly as they are when the order is placed.
     */
    public function previewTotals(Request $request): JsonResponse
    {
        $company = tenant() ?? abort(404);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.sku_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:9999'],
            'delivery_state' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->orderService->previewTotals(
                    $validated['items'],
                    $company->id,
                    $validated['delivery_state'] ?? null,
                ),
            ]);
        } catch (Throwable $e) {
            // A cart holding a SKU that has since been removed or deactivated
            // must not block the page. The checkout itself will refuse the
            // order and say why.
            Log::warning('[OrderController] Cart preview failed', [
                'company' => $company->slug,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Could not calculate totals.'], 422);
        }
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $company = tenant() ?? abort(404);

        try {
            // ── Merge company context into validated data ──
            $data = array_merge($request->validated(), [
                'source' => 'storefront',
                'order_type' => 'retail',                
                
                // Track registered customer ID only when the session actually
                // belongs to a customer of THIS company. See resolveCustomerId().
                'customer_id' => $this->resolveCustomerId($company),
            ]);

            // ── Place order via service ──
            $order = $this->orderService->placeOrder($data, $company->id);

            Log::info('[OrderController] Order placed successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'company' => $company->slug,
                'customer' => $order->customer_phone,
                'total' => $order->total_amount,
            ]);

            // ── Send inquiry emails to customer + owner ──
            $productName = $order->items->first()?->product_name ?? '';
            app(EmailService::class)->sendCustomerInquiryConfirmation($order, $company, $productName);

            // ── Build WhatsApp URL for owner notification ──
            // Triggered from frontend — opens WhatsApp on customer device
            $whatsapp = get_setting('whatsapp', null, $company->id);
            $waUrl = null;
            if ($whatsapp) {
                $waUrl = 'https://wa.me/'
                    .preg_replace('/[^0-9]/', '', $whatsapp)
                    .'?text='.$order->whatsapp_message;
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully!',
                'order_number' => $order->order_number,
                'total' => '₹'.number_format($order->total_amount, 2),
                'whatsapp_url' => $waUrl,
                'receipt_url' => route('storefront.orders.receipt', [
                    'slug' => $company->slug,
                    'orderNumber' => $order->order_number,
                ]),
                'items_count' => $order->items_count,
            ], 201);

        } catch (\InvalidArgumentException $e) {
            // Cart item validation failure — show to customer
            Log::warning('[OrderController] Order validation failed', [                
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('[OrderController] Order placement failed', [                
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again or contact us on WhatsApp.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  CUSTOMER ATTRIBUTION
    //
    //  The app runs a single 'web' guard, so an admin or staff member who is
    //  logged into the backend is also "authenticated" on the public
    //  storefront in the same browser. Auth::id() there is a STAFF id, not a
    //  buyer — writing it to customer_id turns a guest checkout into an order
    //  owned by an employee, and that employee then sees a stranger's order in
    //  their own portal.
    //
    //  An order is attributed only when all three hold:
    //    1. someone is logged in,
    //    2. that user is a customer (not admin/internal/super admin),
    //    3. that customer belongs to the company whose storefront this is.
    //
    //  Anything else is a guest checkout — customer_id stays null.
    // ════════════════════════════════════════════════════

    protected function resolveCustomerId(Company $company): ?int
    {
        $user = Auth::user();

        if (! $user || ! $user->isCustomer()) {
            return null;
        }

        // A customer of another tenant browsing this storefront is a guest here.
        if ((int) $user->company_id !== (int) $company->id) {
            return null;
        }

        // customer_id points at clients, not users. Registration creates the
        // client profile alongside the user, and the IsCustomer middleware
        // refuses a customer without one, so this is populated in practice —
        // but a null here degrades to a guest order rather than failing.
        return $user->client?->id;
    }

    // ════════════════════════════════════════════════════
    //  ORDER CONFIRMATION — GET /{slug}/orders/{orderNumber}
    //  Optional standalone page — useful for sharing
    //  order confirmation link via WhatsApp/SMS
    // ════════════════════════════════════════════════════

    public function show(string $slug, string $orderNumber): View
    {
        $company = tenant() ?? abort(404);

        $order = Order::where('company_id', $company->id)
            ->where('order_number', $orderNumber)
            ->with(['items'])
            ->firstOrFail();

        $navCategories = app(StorefrontController::class)
            ->getNavCategories($company->id);

        Log::info('[OrderController] Order confirmation viewed', [
            'order_number' => $orderNumber,            
        ]);

        return view('storefront.order-confirmation', compact(
            'company',
            'order',
            'navCategories',
        ));
    }
    // ════════════════════════════════════════════════════
    //  DOWNLOAD RECEIPT PDF
    //  GET /admin/orders/{order}/receipt  (admin)
    //  GET /{slug}/orders/{number}/receipt (customer)
    // ════════════════════════════════════════════════════

    public function downloadReceipt(string $slug, string $orderNumber)
    {
        $company = tenant() ?? abort(404);

        $order = Order::where('company_id', $company->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'payments'])
            ->firstOrFail();

        $pdf = Pdf::loadView('storefront.receipt', compact('company', 'order'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'helvetica',
            ]);

        $safeNumber = str_replace(['/', '\\'], '-', $order->order_number);

        return $pdf->download('Receipt-'.$safeNumber.'.pdf');
    }
}
