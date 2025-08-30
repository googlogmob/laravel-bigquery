<?php

declare(strict_types=1);

namespace googlogmob\BigQuery\Cache;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use DateMalformedIntervalStringException;

class CacheItem implements CacheItemInterface
{
    private mixed $value;

    private ?DateTimeInterface $expires;

    /**
     * @param string $key
     * @param mixed $value
     * @param bool $hit
     */
    public function __construct(private readonly string $key, mixed $value = null, private readonly bool $hit = false)
    {
        $this->value = $this->hit ? $value : null;
    }

    /**
     * Retrieves the key.
     *
     * @return string The key associated with the instance.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Retrieves the stored value.
     *
     * @return mixed The value stored in the class.
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * Determines if the current item is a cache hit.
     *
     * @return bool True if the item is a cache hit, false otherwise.
     */
    public function isHit(): bool
    {
        return $this->hit;
    }

    /**
     * Sets the value for the class and returns the current instance.
     *
     * @param mixed $value The value to be set.
     * @return static The current instance of the class.
     */
    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets the expiration time for the current instance.
     *
     * @param DateTimeInterface|null $expiration The expiration date and time, or null to remove expiration.
     * @return static The current instance with the updated expiration time.
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expires = $expiration;

        return $this;
    }

    /**
     * Sets the expiration time for the current instance.
     *
     * @param DateInterval|int|null $time The time period after which the instance should expire.
     *                                    It can be a DateInterval object, an integer (seconds),
     *                                    or null to remove the expiration.
     * @return static The current instance with the updated expiration time.
     * @throws DateMalformedIntervalStringException
     */
    public function expiresAfter(int|DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expires = null;

            return $this;
        }

        $this->expires = new DateTimeImmutable();

        if ($time instanceof DateInterval === false) {
            $time = new DateInterval(sprintf('PT%sS', $time));
        }

        $this->expires = $this->expires->add($time);

        return $this;
    }

    /**
     * Retrieves the expiration date and time.
     *
     * @return DateTimeInterface|null The expiration date and time, or null if not set.
     */
    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expires;
    }
}
