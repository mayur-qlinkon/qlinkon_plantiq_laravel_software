<?php

namespace App\Http\Controllers\Storefront;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\Company;
use App\Models\Banner;
use App\Models\Order;
use App\Models\Product;
use App\Models\CompanySubscription;
use App\Models\StorefrontSection;

use App\Services\Platform\EmailService;
use App\Services\BannerService;
use App\Services\StorefrontSectionService;

use App\Events\Orders\OrderPlaced;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StorefrontController extends Controller
{
    public function __construct(
        protected StorefrontSectionService $sectionService,
        protected BannerService $bannerService,
    ) {}

    // ════════════════════════════════════════════════════
    //  HOMEPAGE
    // ════════════════════════════════════════════════════

    public function index()
    {
        $company = $this->resolveCompany();

        $hasStorefront = $this->companyHasModule($company, 'storefront');
        $hasPlantEducation = $this->companyHasModule($company, 'plant_education');

        /*
        |--------------------------------------------------------------------------
        | Access Rules
        |--------------------------------------------------------------------------
        |
        | 1. Storefront only           => Show website
        | 2. Storefront + Plant Edu    => Show website
        | 3. Plant Edu only            => Redirect to first product
        | 4. Neither module            => 404
        |
        */
        
        // Company has neither Storefront nor Plant Education
        if (! $hasStorefront && ! $hasPlantEducation) {
            abort(404);
        }

        // Company has only Plant Education
        if ($hasPlantEducation && ! $hasStorefront) {

            $firstProduct = Product::where('company_id', $company->id)
                ->where('is_active', true)
                ->where('show_in_storefront', true)
                ->orderBy('id')
                ->first();

            if (! $firstProduct) {
                abort(404);
            }

            return redirect()->route('storefront.product', [
                'slug'        => $company->slug,
                'productSlug' => $firstProduct->slug,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Storefront Homepage
        |--------------------------------------------------------------------------
        */

        // Hero banners
        $heroBanners = $this->safeGetBanners($company->id, 'home_top');

        // Homepage sections
        $sections = $this->sectionService->getLiveSectionsWithProducts($company->id);

        // Navigation categories
        $navCategories = $this->getNavCategories($company->id);

        Log::info('[Storefront] Homepage loaded', [
            'company' => $company->slug,
            'sections' => $sections->count(),
            'banners' => $heroBanners->count(),
        ]);

        return view('storefront.index', compact(
            'company',
            'heroBanners',
            'sections',
            'navCategories',
        ));
    }

    // ════════════════════════════════════════════════════
    //  CATEGORY PAGE
    // ════════════════════════════════════════════════════

    public function category(Request $request)
    {
        // FIX: Extract categorySlug directly from route parameters by name
        $categorySlug = $request->route('categorySlug');
        
        $company = $this->resolveCompany();
        $category = Category::where('company_id', $company->id)
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->firstOrFail();

        // ── Category banner if exists ──
        $categoryBanners = $this->safeGetBanners($company->id, 'category_page', $category->id);

        // ── Products via pivot — sorted by pivot sort_order ──
        $perPage = 16;
        $sortBy = $request->get('sort', 'default');

        $query = Product::withoutGlobalScope('tenant')
            ->where('products.company_id', $company->id)
            ->where('products.is_active', true)
            ->where('products.show_in_storefront', true)
            ->whereNull('products.deleted_at')
            ->whereHas('categoryPivots', fn ($q) => $q->where('category_id', $category->id)
                ->where('category_products.is_active', true)
            )
            ->with([
                'media' => fn ($q) => $q->where('is_primary', true)->limit(1),
                'skus' => fn ($q) => $q->limit(1),
            ])
            ->join('category_products as cp',
                fn ($join) => $join
                    ->on('products.id', '=', 'cp.product_id')
                    ->where('cp.category_id', $category->id)
            )
            ->select('products.*');

        // Apply sort
        match ($sortBy) {
            'price_asc' => $query->orderBy(
                DB::table('product_skus')
                    ->selectRaw('min(price)')
                    ->whereColumn('product_id', 'products.id'),
                'asc'
            ),
            'price_desc' => $query->orderBy(
                DB::table('product_skus')
                    ->selectRaw('max(price)')
                    ->whereColumn('product_id', 'products.id'),
                'desc'
            ),
            'newest' => $query->orderBy('products.created_at', 'desc'),
            'name_asc' => $query->orderBy('products.name', 'asc'),
            default => $query->orderBy('cp.is_featured', 'desc')->orderBy('cp.sort_order', 'asc'),
        };

        $products = $query->paginate($perPage)->withQueryString();

        // ── Nav categories ──
        $navCategories = $this->getNavCategories($company->id);

        // ── All categories for sidebar filter ──
        $allCategories = Category::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        Log::info('[Storefront] Category page loaded', [
            'company' => $company->slug,
            'category' => $category->slug,
            'products' => $products->total(),
            'sort' => $sortBy,
        ]);

        return view('storefront.category', compact(
            'company',
            'category',
            'products',
            'navCategories',
            'allCategories',
            'categoryBanners',
            'sortBy',
        ));
    }

    // ════════════════════════════════════════════════════
    //  PRODUCT DETAIL
    // ════════════════════════════════════════════════════

    public function show(Request $request)
    {
        // FIX: Extract productSlug directly from route parameters by name
        $productSlug = $request->route('productSlug');
        
        $company = $this->resolveCompany();

        $product = Product::with([
            'media',
            'skus.skuValues.attribute',
            'skus.skuValues.attributeValue',
            'skus.stocks:id,product_sku_id,qty', // needed by ProductSku::getIsInStockAttribute — prevents N+1
            'categories' => fn ($q) => $q->where('company_id', $company->id),
            'productUnit',
            'saleUnit',
        ])
            ->where('company_id', $company->id)
            ->where('slug', $productSlug)
            ->where('is_active', true)
            ->where('show_in_storefront', true)
            ->firstOrFail();

        // ── Related products from same category ──
        $related = collect();
        $primaryCategory = $product->categories->first();
        if ($primaryCategory) {
            $related = Product::where('company_id', $company->id)
                ->where('id', '!=', $product->id)
                ->where('is_active', true)
                ->where('show_in_storefront', true)
                ->whereHas('categoryPivots', fn ($q) => $q->where('category_id', $primaryCategory->id)->where('is_active', true)
                )
                ->with(['media' => fn ($q) => $q->where('is_primary', true)->limit(1), 'skus' => fn ($q) => $q->limit(1)])
                ->limit(6)
                ->get();
        }

        // ── "Also Add" cross-sell — products flagged show_as_addon, grouped by their category ──
        $addonGroups = Product::where('company_id', $company->id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->where('show_in_storefront', true)
            ->where('show_as_addon', true)
            ->with([
                'media' => fn ($q) => $q->where('is_primary', true)->limit(1),
                'skus' => fn ($q) => $q->orderBy('price', 'asc'),
                'skus.stocks:id,product_sku_id,qty',
                'categories' => fn ($q) => $q->where('company_id', $company->id),
            ])
            ->get()
            // Group by first category name — products with no category fall under "More Add-ons"
            ->groupBy(fn ($p) => $p->categories->first()->name ?? 'More Add-ons');

        $navCategories = $this->getNavCategories($company->id);

        // Track product view (future: increment view counter)
        Log::info('[Storefront] Product viewed', [
            'company' => $company->slug,
            'product' => $product->slug,
            'id' => $product->id,
        ]);
        $hasStorefront = $this->companyHasModule($company, 'storefront');
        $hasPlantEducation = $this->companyHasModule($company, 'plant_education');


        return view('storefront.product', compact(
            'company',
            'product',
            'related',
            'addonGroups',
            'navCategories',
            'hasStorefront',
            'hasPlantEducation',
        ));
    }

    // ════════════════════════════════════════════════════
    //  SEARCH
    // ════════════════════════════════════════════════════

    public function search(Request $request)
    {
        $company = $this->resolveCompany();
        $query = trim($request->get('q', ''));
        $perPage = 16;

        $products = collect();
        $total = 0;

        if (strlen($query) >= 2) {
            $result = Product::where('company_id', $company->id)
                ->where('is_active', true)
                ->where('show_in_storefront', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                        ->orWhere('hsn_code', 'like', "%{$query}%")
                        ->orWhereHas('skus', fn ($sq) => $sq->where('sku', 'like', "%{$query}%")
                        );
                })
                ->with([
                    'media' => fn ($q) => $q->where('is_primary', true)->limit(1),
                    'skus' => fn ($q) => $q->limit(1),
                ])
                ->latest()
                ->paginate($perPage)
                ->withQueryString();

            $products = $result;
            $total = $result->total();
        }

        $navCategories = $this->getNavCategories($company->id);

        Log::info('[Storefront] Search', [
            'company' => $company->slug,
            'query' => $query,
            'results' => $total,
        ]);

        return view('storefront.search', compact(
            'company',
            'products',
            'navCategories',
            'query',
            'total',
        ));
    }

    // ════════════════════════════════════════════════════
    //  SUGGEST — AJAX dropdown (header search)
    // ════════════════════════════════════════════════════

    public function suggest(Request $request): JsonResponse
    {
        $company = $this->resolveCompany();
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['products' => []]);
        }

        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('show_in_storefront', true)
            ->where('name', 'like', "%{$query}%")
            ->with([
                'media' => fn ($q) => $q->where('is_primary', true)->limit(1),
                'skus' => fn ($q) => $q->orderBy('price')->limit(1),
            ])
            ->limit(8)
            ->get()
            ->map(fn ($product) => [
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->primary_image_url,
                'price' => $product->skus->first()?->price ?? 0,
                'product_type' => $product->product_type ?? 'sellable',
            ]);

        return response()->json(['products' => $products]);
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════
    // ════════════════════════════════════════════════════
    //  COMPANY-LEVEL MODULE CHECK
    //  Unlike has_module() (which checks the *logged-in user's*
    //  company + seat access), storefront visitors are anonymous —
    //  this checks the resolved tenant's own active subscription
    //  plan directly, regardless of who is browsing.
    // ════════════════════════════════════════════════════

    private function companyHasModule(Company $company, string $moduleSlug): bool
    {
        // activeFor() rather than a query of its own: identical conditions, but
        // cached and invalidated centrally. This ran uncached on every public
        // storefront page load.
        $subscription = CompanySubscription::activeFor($company->id);

        if (! $subscription || ! $subscription->plan) {
            return false;
        }

        return $subscription->plan->modules->contains('slug', $moduleSlug);
    }    
    /**
     * Resolve company by domain OR slug — 404 if not found.
     */
    private function resolveCompany(): Company
    {
        // Slug route param takes HIGHEST priority — when URL has /{slug}/...,
        // the slug's company is ALWAYS correct, regardless of what host resolved.
        // This is the key that makes slug + subdomain + custom domain all work
        // from one controller with zero duplication.
        $routeSlug = request()->route('slug');

        if ($routeSlug) {
            $company = Company::where('slug', $routeSlug)
                ->where('is_active', true)
                ->first();

            if ($company) {
                return $company;
            }
        }

        // No slug in route → host-based tenant (subdomain / custom domain).
        $company = tenant();

        if (! $company) {
            abort(404);
        }

        return $company;
    }

    /**
     * Get banners safely — never crashes the page.
     * Falls back to empty collection on any error.
     */
    private function safeGetBanners(
        int $companyId,
        string $position,
        ?int $categoryId = null,
        ?int $productId = null,
    ): Collection {
        try {
            $now = now();

            return Banner::where('company_id', $companyId)
                ->where('position', $position)
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
                // ── Targeting filter ──
                // If banner has a category_id set, only show it on that category's page
                // If banner has no category_id (null), show it on ALL category pages
                ->where(function ($q) use ($categoryId) {
                    $q->whereNull('category_id');
                    if ($categoryId) {
                        $q->orWhere('category_id', $categoryId);
                    }
                })
                ->where(function ($q) use ($productId) {
                    $q->whereNull('product_id');
                    if ($productId) {
                        $q->orWhere('product_id', $productId);
                    }
                })
                ->orderBy('sort_order')
                ->get();

        } catch (\Throwable $e) {
            Log::warning('[Storefront] Banner load failed', [
                'company_id' => $companyId,
                'position' => $position,
                'error' => $e->getMessage(),
            ]);

            return new Collection;
        }
    }

    /**
     * Get active categories for nav bar — cached per company.
     * Cache: 15 minutes. Invalidate when categories change.
     */
    public function getNavCategories(int $companyId): Collection
    {
        try {
            return Cache::remember(
                "storefront_nav_categories_{$companyId}",
                now()->addMinutes(15),
                fn () => Category::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->limit(12)
                    ->get(['id', 'name', 'slug', 'image'])
            );
        } catch (\Throwable $e) {
            Log::warning('[Storefront] Nav categories load failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return new Collection;
        }
    }

    /**
     * Analytics endpoints are public — no auth, no session identity.
     *
     * That matters more than it looks: Tenantable registers its global scope
     * only when a user is authenticated, so on these routes StorefrontSection
     * carries no tenant condition whatsoever. Both counters were therefore
     * writable across every company by posting an arbitrary section id.
     *
     * The tenant already resolves from the {slug} prefix, so the section is
     * matched against it explicitly instead.
     */
    public function trackView(Request $request)
    {
        $company = tenant();

        if (! $company) {
            return response()->json(['status' => 'ignored']);
        }

        $sectionId = (int) $request->input('section_id');

        if ($sectionId) {
            StorefrontSection::withoutGlobalScope('tenant')
                ->where('id', $sectionId)
                ->where('company_id', $company->id)
                ->increment('view_count');
        }

        return response()->json(['status' => 'logged']);
    }

    public function trackClick(Request $request)
    {
        $company = tenant();

        if (! $company) {
            return response()->json(['status' => 'ignored']);
        }

        $id = (int) $request->route('id');

        if ($id) {
            StorefrontSection::withoutGlobalScope('tenant')
                ->where('id', $id)
                ->where('company_id', $company->id)
                ->increment('click_count');
        }

        return response()->json(['status' => 'logged']);
    }

    /**
     * Handle catalog product inquiry → creates an Order with order_type = 'inquiry'.
     */
    public function inquiry(Request $request)
    {
        $request->validate([
            // Existence is not asserted here: company_exists() reads the
            // authenticated user's company, and this route has no session.
            // Ownership is enforced inside the transaction against the
            // storefront's own company instead.
            'product_id' => ['required', 'integer', 'min:1'],
            'product_name' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $company = $this->resolveCompany();

        // 🛠️ Wrap in transaction to ensure both Order and OrderItem are created
        [$order, $productName] = DB::transaction(function() use ($company, $request) {
            // Scoped exactly as show() scopes the product page. This route is
            // reachable without a session, so Tenantable registers no scope and
            // an unfiltered lookup spans every company on the platform.
            //
            // is_active and show_in_storefront matter as much as company_id: a
            // product deliberately hidden from the storefront must not be
            // reachable by posting its id straight to this endpoint.
            //
            // 404 rather than a validation error — an id that belongs to
            // another tenant should be indistinguishable from one that does
            // not exist, so this cannot be used to enumerate products.
            $product = Product::where('id', $request->product_id)
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->where('show_in_storefront', true)
                ->firstOrFail();

            $sku = $product->skus()->where('is_active', true)->first();

            $order = Order::create([
                'company_id'      => $company->id,
                'order_type'      => 'inquiry', // Essential for conditional display
                'source'          => 'storefront',
                'status'          => 'inquiry',
                'payment_status'  => 'pending',
                'customer_name'   => $request->customer_name,
                'customer_email'  => $request->customer_email,
                'customer_phone'  => $request->customer_phone,
                'customer_notes'  => $request->customer_notes,
                'admin_notes'     => 'Inquiry received for product: ' . $product->name,
                'subtotal'        => 0,
                'total_amount'    => 0,
                'items_count'     => 1,
                'items_qty'       => 1,
            ]);

            OrderItem::create([
                'order_id'      => $order->id,
                'product_id'    => $product->id,
                'sku_id'        => $sku?->id,
                'product_name'  => $product->name,
                'sku_code'      => $sku?->sku ?? $sku?->sku_code,
                'product_image' => $product->primary_image_url,
                'unit_price'    => 0, // Inquiries don't have fixed transaction prices
                'qty'           => 1,
                'line_total'    => 0,
            ]);

            return [$order, $product->name];
        });

        event(new OrderPlaced($order));
        // $product->name, not $request->product_name — the posted value is
        // attacker-controlled and lands verbatim in an email sent from this
        // company's domain.
        app(EmailService::class)->sendCustomerInquiryConfirmation($order, $company, $order->items->first()->product_name);

        return redirect()->back()->with('success', 'Your inquiry has been submitted successfully!');
    }
}