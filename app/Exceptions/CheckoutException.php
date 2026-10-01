<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An expected checkout problem whose message is safe to show to the shopper.
 */
class CheckoutException extends RuntimeException {}
