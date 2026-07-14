<?php

declare(strict_types=1);

namespace Arris\Core\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
    private array $storage = [];
    private array $ttl = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertKey($key);

        if (!$this->has($key)) {
            return $default;
        }

        return $this->storage[$key];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertKey($key);

        $this->storage[$key] = $value;
        $this->ttl[$key] = $this->normalizeTtl($ttl);

        return true;
    }

    public function delete(string $key): bool
    {
        $this->assertKey($key);

        unset($this->storage[$key], $this->ttl[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];
        $this->ttl = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $this->assertKey($key);
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->assertKey($key);
            $this->storage[$key] = $value;
            $this->ttl[$key] = $this->normalizeTtl($ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->assertKey($key);
            unset($this->storage[$key], $this->ttl[$key]);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->assertKey($key);

        if (!array_key_exists($key, $this->storage)) {
            return false;
        }

        $expires = $this->ttl[$key] ?? null;

        if ($expires !== null && $expires <= time()) {
            unset($this->storage[$key], $this->ttl[$key]);
            return false;
        }

        return true;
    }

    private function assertKey(mixed $key): void
    {
        if (!is_string($key) || $key === '' || preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new InvalidArgumentException("Invalid cache key: " . var_export($key, true));
        }
    }

    private function normalizeTtl(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return $ttl > 0 ? time() + $ttl : null;
        }

        return (new DateTimeImmutable())->add($ttl)->getTimestamp();
    }
}
