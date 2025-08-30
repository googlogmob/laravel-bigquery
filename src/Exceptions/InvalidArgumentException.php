<?php

declare(strict_types=1);

namespace googlogmob\BigQuery\Exceptions;

use InvalidArgumentException as BaseInvalidArgumentException;
use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionContract;

class InvalidArgumentException extends BaseInvalidArgumentException implements InvalidArgumentExceptionContract
{
}
