<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderInquiryNotification extends Notification
{
    use Queueable;

    public function __construct(protected Order $order) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'New Order Inquiry',
            'message' => "Order #{$this->order->order_number} received from {$this->order->customer_name}.",
            'link' => route('admin.orders.show', $this->order->id),
            'icon' => 'shopping-cart', // Matches Lucide name
            'color' => 'green',         // UI theme color
            'type' => 'new_order',
        ];
    }
}
