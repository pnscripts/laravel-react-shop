<?php

namespace PnShop\Cms\Exceptions;

use RuntimeException;

/**
 * A change would add, alter or remove blocks of a type the staff member may not edit.
 */
class LockedBlocksException extends RuntimeException {}
