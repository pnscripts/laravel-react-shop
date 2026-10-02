<?php

namespace PnShop\Extension\Exceptions;

use RuntimeException;

/**
 * An expected problem with an extension (invalid manifest, unmet requirement, failed
 * migration, ...) with a message that is safe to show staff.
 */
class ExtensionException extends RuntimeException {}
