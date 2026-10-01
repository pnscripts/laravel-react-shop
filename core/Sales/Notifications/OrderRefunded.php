<?php

namespace PnShop\Sales\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use PnShop\Payment\Models\Refund;

class OrderRefunded extends OrderMail
{
    public function __construct(public Refund $refund)
    {
        parent::__construct($refund->order()->firstOrFail());
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->orderButton($this->message()
            ->subject(__('Refund for order :number', ['number' => $this->order->number]))
            ->line(__('We have refunded :amount for your order :number.', ['amount' => $this->money($this->refund->amount), 'number' => $this->order->number]))
            ->line(__('Depending on your bank, it can take a few days to appear.')));
    }
}
