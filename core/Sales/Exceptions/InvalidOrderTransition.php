<?php

namespace PnShop\Sales\Exceptions;

use PnShop\Sales\States\OrderState;

/**
 * A state change the order's state machine does not allow. The message is safe to show staff.
 */
final class InvalidOrderTransition extends OrderException
{
    public static function between(OrderState $from, OrderState $to): self
    {
        return new self(__(':field cannot change from ":from" to ":to".', [
            'field' => __($from::fieldLabel()),
            'from' => __($from->label()),
            'to' => __($to->label()),
        ]));
    }

    public static function cancelled(): self
    {
        return new self(__('Reopen the cancelled order first.'));
    }
}
