<?php

namespace PnShop\Sales\Exceptions;

use RuntimeException;

/**
 * An expected problem while changing an order, with a message that is safe to show staff.
 */
class OrderException extends RuntimeException {}
