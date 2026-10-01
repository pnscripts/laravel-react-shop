<?php

namespace PnShop\Sales\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use PnShop\Payment\PaymentService;

class OrderConfirmation extends OrderMail
{
    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing(['items']);
        $message = $this->message()
            ->subject(__('Order :number confirmed', ['number' => $order->number]))
            ->line(__('We have received your order :number.', ['number' => $order->number]));

        $this->itemLines($message)
            ->line(__('Total: :amount', ['amount' => $this->money($order->grandTotal())]));

        if ($order->shipping_method_name !== null) {
            $message->line(__('Delivery: :method', ['method' => $order->shipping_method_name]));
        }

        $instructions = app(PaymentService::class)->instructions($order);

        if ($instructions !== null) {
            $message->line('**'.__('How to pay').'**');

            foreach (preg_split('/\R/', $instructions) ?: [] as $line) {
                $message->line($line);
            }
        }

        return $this->orderButton($message);
    }
}
