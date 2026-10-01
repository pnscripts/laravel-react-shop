<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An expected cart problem whose message is safe to show to the shopper.
 */
class CartException extends RuntimeException {}
