<?php

declare(strict_types=1);

namespace Ermtraud\XsdToPhp\Exception;

use InvalidArgumentException;

/**
 * Thrown when XML mapper configuration contains invalid values.
 */
final class InvalidConfiguration extends InvalidArgumentException
{
}
