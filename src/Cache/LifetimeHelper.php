<?php

declare(strict_types=1);

namespace googlogmob\BigQuery\Cache;

use ReflectionClass;
use DateTimeImmutable;
use DateTimeInterface;
use ReflectionException;
use DateMalformedStringException;
use Illuminate\Contracts\Cache\Store;

class LifetimeHelper
{
    /**
     * Computes the lifetime interval between the current time and the provided expiration time.
     *
     * @param DateTimeInterface $expiresAt The expiration time to compare against the current time.
     * @return int|float The computed lifetime, in seconds for non-legacy mode or minutes for legacy mode.
     * @throws DateMalformedStringException|ReflectionException
     */
    public static function computeLifetime(DateTimeInterface $expiresAt): int|float
    {
        $now = new DateTimeImmutable('now', $expiresAt->getTimezone());

        $seconds = $expiresAt->getTimestamp() - $now->getTimestamp();

        return self::isLegacy() ? (int)floor($seconds / 60.0) : $seconds;
    }

    /**
     * Determines whether the system is in legacy mode based on the parameter names of the 'put' method in the Store class.
     *
     * @return bool True if the system operates in legacy mode, false otherwise.
     * @throws ReflectionException
     */
    private static function isLegacy(): bool
    {
        static $legacy;

        if ($legacy === null) {
            $params = new ReflectionClass(Store::class)->getMethod('put')->getParameters();
            $legacy = $params[2]->getName() === 'minutes';
        }

        return $legacy;
    }
}
