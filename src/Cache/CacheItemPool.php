<?php

declare(strict_types=1);

namespace googlogmob\BigQuery\Cache;

use Exception;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use Illuminate\Contracts\Cache\Store;
use Psr\Cache\CacheItemPoolInterface;
use Illuminate\Contracts\Cache\Repository;
use googlogmob\BigQuery\Exceptions\InvalidArgumentException;

class CacheItemPool implements CacheItemPoolInterface
{
    private const string INVALID_KEY_PATTERN = '#[{}\(\)/\\\\@:]#';

    /**
     * @var CacheItemInterface[]
     */
    private array $deferred = [];

    /**
     * Constructor method for initializing the class with a Repository instance.
     *
     * @param Repository $repository The repository instance to be used by the class.
     * @return void
     */
    public function __construct(private readonly Repository $repository)
    {
    }

    /**
     * Performs necessary cleanup operations before the object is destroyed.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->commit();
    }

    /**
     * Retrieves a cache item by its unique key.
     *
     * @param string $key The unique key of the cache item to retrieve.
     * @return CacheItemInterface The cache item associated with the specified key.
     * @throws \Psr\Cache\InvalidArgumentException|\Psr\SimpleCache\InvalidArgumentException
     */
    public function getItem(string $key): CacheItemInterface
    {
        $this->validateKey($key);

        if (isset($this->deferred[$key])) {
            return clone($this->deferred[$key]);
        }

        if ($this->repository->has($key)) {
            return new CacheItem($key, unserialize($this->repository->get($key), []), true);
        }

        return new CacheItem($key);
    }

    /**
     * Retrieves multiple cache items by their unique keys.
     *
     * @param array $keys An array of unique keys for the cache items to retrieve.
     * @return iterable An iterable collection of cache items, keyed by the specified keys.
     * @throws \Psr\Cache\InvalidArgumentException|\Psr\SimpleCache\InvalidArgumentException
     */
    public function getItems(array $keys = []): iterable
    {
        return array_combine($keys, array_map(fn (string $key): CacheItemInterface => $this->getItem($key), $keys));
    }

    /**
     * Checks if a cache item exists in the cache pool.
     *
     * @param string $key The unique key of the cache item to check.
     * @return bool True if the cache item exists, false otherwise.
     * @throws \Psr\Cache\InvalidArgumentException|\Psr\SimpleCache\InvalidArgumentException
     */
    public function hasItem(string $key): bool
    {
        $this->validateKey($key);

        if (isset($this->deferred[$key])) {
            $item = $this->deferred[$key];
            $expiresAt = $this->getExpiresAt($item);

            if (!$expiresAt) {
                return true;
            }

            return $expiresAt > new DateTimeImmutable();
        }

        return $this->repository->has($key);
    }

    /**
     * Clears all items from the cache.
     *
     * @return bool True if the cache was successfully cleared, false otherwise.
     */
    public function clear(): bool
    {
        try {
            $this->deferred = [];
            $store = $this->repository;
            /* @var Store $store */
            $store->flush();
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a cache item by its unique key.
     *
     * @param string $key The unique key of the cache item to delete.
     * @return bool True if the cache item was successfully deleted or does not exist; false otherwise.
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deleteItem(string $key): bool
    {
        $this->validateKey($key);

        unset($this->deferred[$key]);

        if (!$this->hasItem($key)) {
            return true;
        }

        return $this->repository->forget($key);
    }

    /**
     * Deletes multiple cache items identified by their unique keys.
     *
     * @param array $keys An array of unique keys for the cache items to be deleted.
     * @return bool True if all cache items were successfully deleted, false otherwise.
     * @throws \Psr\Cache\InvalidArgumentException|\Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteItems(array $keys): bool
    {
        // Validating all keys first.
        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        $success = true;

        foreach ($keys as $key) {
            $success = $success && $this->deleteItem($key);
        }

        return $success;
    }

    /**
     * Saves a cache item to the repository storage.
     *
     * @param CacheItemInterface $item The cache item to be saved.
     * @return bool True on successful save, false if the save operation fails or the item has expired.
     */
    public function save(CacheItemInterface $item): bool
    {
        $expiresAt = $this->getExpiresAt($item);

        if (!$expiresAt) {
            try {
                $this->repository->forever($item->getKey(), serialize($item->get()));
            } catch (Exception) {
                return false;
            }

            return true;
        }

        $lifetime = LifetimeHelper::computeLifetime($expiresAt);

        if ($lifetime <= 0) {
            $this->repository->forget($item->getKey());

            return false;
        }

        try {
            $this->repository->put($item->getKey(), serialize($item->get()), $lifetime);
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Saves a cache item for deferred persistence.
     *
     * @param CacheItemInterface $item The cache item to save for deferred persistence.
     * @return bool True if the item is successfully queued for deferred persistence, false otherwise.
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $expiresAt = $this->getExpiresAt($item);

        if ($expiresAt && ($expiresAt < new DateTimeImmutable())) {
            return false;
        }

        $item = new CacheItem($item->getKey(), $item->get(), true)->expiresAt($expiresAt);

        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * Persists all deferred cache items to the storage.
     *
     * @return bool True if all deferred items were successfully saved, false otherwise.
     */
    public function commit(): bool
    {
        $success = true;

        foreach ($this->deferred as $item) {
            $success = $success && $this->save($item);
        }

        $this->deferred = [];

        return $success;
    }

    /**
     * Validates the given cache key to ensure it meets the requirements.
     *
     * @param string $key The cache key to validate.
     * @return void
     * @throws InvalidArgumentException If the key contains invalid characters.
     */
    private function validateKey(string $key): void
    {
        if (preg_match(self::INVALID_KEY_PATTERN, $key)) {
            throw new InvalidArgumentException(sprintf('Cache key "%s" contains invalid characters: {}()/\@: ', $key));
        }
    }

    /**
     * Retrieves the expiration date and time of a cache item.
     *
     * @param CacheItemInterface $item The cache item to retrieve the expiration date from.
     * @return DateTimeInterface|null The expiration date and time if set, or null if no expiration is configured.
     */
    private function getExpiresAt(CacheItemInterface $item): ?DateTimeInterface
    {
        return $item->getExpiresAt();
    }
}
