<?php

declare(strict_types=1);

namespace Arris\Util;

use ArrayIterator;
use Traversable;

class Collection implements CollectionInterface
{
    private array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    public static function of(array $items = []): static
    {
        return new static($items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return $this->items !== [];
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function last(): mixed
    {
        return $this->items[count($this->items) - 1] ?? null;
    }

    public function get(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function indexOf(mixed $value): int|string|false
    {
        return array_search($value, $this->items, true);
    }

    public function keys(): array
    {
        return array_keys($this->items);
    }

    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items));
    }

    public function filter(?callable $callback = null): static
    {
        return new static(array_filter($this->items, $callback ?? fn($v) => (bool) $v));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }
        return $this;
    }

    public function sort(callable $callback): static
    {
        $items = $this->items;
        usort($items, $callback);
        return new static($items);
    }

    public function sortDesc(): static
    {
        $items = $this->items;
        rsort($items);
        return new static($items);
    }

    public function unique(): static
    {
        return new static(array_unique($this->items, SORT_REGULAR));
    }

    public function reverse(): static
    {
        return new static(array_reverse($this->items));
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length));
    }

    public function merge(CollectionInterface|array $items): static
    {
        $other = $items instanceof CollectionInterface ? $items->all() : $items;
        return new static(array_merge($this->items, array_values($other)));
    }

    public function diff(CollectionInterface|array $items): static
    {
        $other = $items instanceof CollectionInterface ? $items->all() : $items;
        return new static(array_values(array_diff($this->items, $other)));
    }

    public function intersect(CollectionInterface|array $items): static
    {
        $other = $items instanceof CollectionInterface ? $items->all() : $items;
        return new static(array_values(array_intersect($this->items, $other)));
    }

    public function pluck(string $key): array
    {
        return array_map(fn($item) => $item[$key] ?? null, $this->items);
    }

    public function groupBy(callable|string $key): array
    {
        $result = [];

        foreach ($this->items as $item) {
            $groupKey = is_callable($key) ? $key($item) : (is_array($item) ? $item[$key] : $item->$key);
            $result[$groupKey][] = $item;
        }

        return $result;
    }

    public function keyBy(callable|string $key): static
    {
        $result = [];

        foreach ($this->items as $item) {
            $k = is_callable($key) ? $key($item) : (is_array($item) ? $item[$key] : $item->$key);
            $result[$k] = $item;
        }

        $instance = new static([]);
        $instance->items = $result;
        return $instance;
    }

    public function implode(string $glue = ','): string
    {
        return implode($glue, $this->items);
    }

    public function chunk(int $size): array
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return $chunks;
    }

    public function random(): mixed
    {
        if ($this->items === []) {
            return null;
        }
        return $this->items[array_rand($this->items)];
    }

    public function shuffle(): static
    {
        $items = $this->items;
        shuffle($items);
        return new static($items);
    }

    public function add(mixed $item): void
    {
        $this->items[] = $item;
    }

    public function push(mixed ...$items): void
    {
        array_push($this->items, ...$items);
    }

    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    public function unshift(mixed ...$items): void
    {
        array_unshift($this->items, ...$items);
    }

    public function search(callable $callback): mixed
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }
        return null;
    }

    public function every(callable $callback): bool
    {
        foreach ($this->items as $key => $item) {
            if (!$callback($item, $key)) {
                return false;
            }
        }
        return true;
    }

    public function some(callable $callback): bool
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return true;
            }
        }
        return false;
    }

    public function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }

    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
