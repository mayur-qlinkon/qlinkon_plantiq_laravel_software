<?php

namespace App\Listeners\Orders;

use App\Enums\NotificationEvent;
use App\Events\Orders\OrderPlaced;
use App\Mail\DynamicMail;
use App\Notifications\Orders\NewOrderInquiryNotification;
use App\Services\NotificationDispatcher;

/**
 * Notifies the tenant's staff about a new storefront inquiry.
 *
 * Recipient resolution used to live here — ~30 lines duplicated in the leave
 * listener, and the owner's email copy went to a single fixed company address
 * that ignored Settings > Notifications entirely. Both now go through the
 * dispatcher.
 */
class HandleNewOrderNotification
{
    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $company = $order->company;

        if (! $company) {
            return;
        }

        $productName = $order->items->first()?->productSku?->product?->name ?? '';

        $data = [
            'customerName' => $order->customer_name ?? '',
            'customerEmail' => $order->customer_email ?? '',
            'customerPhone' => $order->customer_phone ?? '',
            'productName' => $productName,
            'message' => $order->customer_notes ?? '',
            'storeName' => $company->name,
            'orderNumber' => $order->order_number,
            'inquiryDate' => $order->created_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A'),
        ];

        $this->dispatcher->dispatch(
            NotificationEvent::OrderPlaced,
            $company->id,
            notification: new NewOrderInquiryNotification($order),
            mailable: new DynamicMail(
                "New inquiry from {$data['customerName']} — {$data['orderNumber']}",
                'emails.order-inquiry-owner',
                $data,
            ),
        );
    }
}
