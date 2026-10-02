<?php

namespace PnShop\Sales\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class OrderCancelled extends OrderMail
{
    public function toMail(object $notifiable): MailMessage
    {
        return $this->orderButton($this->message()
            ->subject(__('Order :number cancelled', ['number' => $this->order->number]))
            ->line(__('Your order :number has been cancelled.', ['number' => $this->order->number]))
            ->line(__('If you have already paid, the payment will be refunded. Reply to this email if you have any questions.')));
    }
}
