<?php

namespace googlogmob\BigQuery\Exceptions;

use InvalidArgumentException AS BaseInvalidArgumentException;
use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionContract;

class InvalidArgumentException extends BaseInvalidArgumentException implements InvalidArgumentExceptionContract
{

}