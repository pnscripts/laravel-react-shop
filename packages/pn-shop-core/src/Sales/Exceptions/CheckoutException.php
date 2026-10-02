<?php

namespace PnShop\Sales\Exceptions;

use RuntimeException;

/**
 * An expected checkout problem whose message is safe to show to the shopper.
 */
class CheckoutException extends RuntimeException {}
