<?php

namespace PnShop\Sales\Notifications;

use Illuminate\Support\Facades\Notification;
use PnShop\Payment\Events\RefundCompleted;
use PnShop\Sales\Events\OrderPlaced;
use PnShop\Sales\Events\OrderStateChanged;
use PnShop\Sales\Models\Order;
use PnShop\Sales\States\OrderStatus;
use PnShop\Settings\Settings;
use PnShop\Shipping\Events\ShipmentCreated;

/**
 * Sends the order emails the store has switched on (Admin → Settings → Emails).
 */
class SendOrderNotifications
{
    public function __construct(private Settings $settings) {}

    public function handle(OrderPlaced|ShipmentCreated|OrderStateChanged|RefundCompleted $event): void
    {
        match (true) {
            $event instanceof OrderPlaced => $this->placed($event->order),
            $event instanceof ShipmentCreated => $this->toCustomer($event->shipment->order, 'notifications.shipping_updates', fn () => new OrderShipped($event->shipment)),
            $event instanceof RefundCompleted => $this->toCustomer($event->refund->order, 'notifications.refunds', fn () => new OrderRefunded($event->refund)),
            $event->to === OrderStatus::Cancelled => $this->toCustomer($event->order, 'notifications.cancellations', fn () => new OrderCancelled($event->order)),
            default => null,
        };
    }

    private function placed(Order $order): void
    {
        $this->toCustomer($order, 'notifications.order_confirmation', fn () => new OrderConfirmation($order));

        $staff = trim((string) ($this->settings->get('notifications.staff_email') ?: $this->settings->get('store.email')));

        if ($this->settings->get('notifications.staff_new_order') && $staff !== '') {
            Notification::route('mail', $staff)->notify(new NewOrderForStaff($order));
        }
    }

    /**
     * @param  \Closure(): OrderMail  $notification
     */
    private function toCustomer(?Order $order, string $setting, \Closure $notification): void
    {
        if ($order !== null && $order->email !== '' && $this->settings->get($setting)) {
            Notification::route('mail', $order->email)->notify($notification());
        }
    }
}
