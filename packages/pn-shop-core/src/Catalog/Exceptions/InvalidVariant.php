<?php

namespace PnShop\Catalog\Exceptions;

use RuntimeException;

/**
 * A variant change that the catalog rules refuse (duplicate options, the last variant…).
 * The message is shown to staff as is; `field` names the input it is about.
 */
final class InvalidVariant extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'variant')
    {
        parent::__construct($message);
    }
}
