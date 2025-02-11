<?php

namespace App\Notifications;

use App\Models\PurchaseOrders;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PurchaseOrderCompleted extends Notification
{
    use Queueable;

    public function __construct(
        protected PurchaseOrders $purchaseOrder
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'message' => "Purchase order #{$this->purchaseOrder->id} for {$this->purchaseOrder->product_name} has been completed",
            'purchase_order_id' => $this->purchaseOrder->id,
            'product_name' => $this->purchaseOrder->product_name,
            'quantity' => $this->purchaseOrder->quantity,
            'status' => $this->purchaseOrder->order_status,
        ]);
    }
}
