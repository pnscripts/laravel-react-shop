<?php

namespace PnShop\Sales\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Shipping\Models\Shipment;
use PnShop\Shipping\Models\ShipmentLine;

class OrderShipped extends OrderMail
{
    public function __construct(public Shipment $shipment)
    {
        parent::__construct($shipment->order()->firstOrFail());
    }

    public function toMail(object $notifiable): MailMessage
    {
        $complete = $this->order->fulfillment_status === FulfillmentStatus::Fulfilled;

        $message = $this->message()
            ->subject($complete
                ? __('Your order :number has shipped', ['number' => $this->order->number])
                : __('Part of your order :number has shipped', ['number' => $this->order->number]))
            ->line($complete ? __('Good news: your order is on its way.') : __('Good news: part of your order is on its way. We will let you know when the rest ships.'));

        foreach ($this->shipment->lines()->with('item')->get() as $line) {
            /** @var ShipmentLine $line */
            $message->line($line->quantity.' × '.$line->item?->product_title);
        }

        if ($this->shipment->tracking_number !== null) {
            $message->line(__('Tracking number: :number', ['number' => $this->shipment->tracking_number]));
        }

        if ($this->shipment->tracking_url !== null) {
            return $message->action(__('Track your parcel'), $this->shipment->tracking_url);
        }

        return $this->orderButton($message);
    }
}
