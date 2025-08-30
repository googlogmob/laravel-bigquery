<?php

declare(strict_types=1);

namespace googlogmob\BigQuery\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    /**
     * Creates a new instance of the class with an error message indicating
     * that the credentials JSON file could not be found at the specified path.
     *
     * @param string $path The file path where the credentials JSON file is expected.
     * @return self Returns an instance of the class with the specified error message.
     */
    public static function credentialsJsonDoesNotExist(string $path): self
    {
        return new self(sprintf('Could not find a credentials file at `%s`.', $path));
    }
}
