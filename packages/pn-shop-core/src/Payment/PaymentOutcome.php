<?php

namespace PnShop\Payment;

enum PaymentOutcome: string
{
    case Paid = 'paid';
    case Authorized = 'authorized';
    case Pending = 'pending';
    case Redirect = 'redirect';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
