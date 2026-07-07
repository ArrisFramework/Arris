<?php

declare(strict_types=1);

namespace Arris\Util;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;

interface CollectionInterface extends ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    public static function of(array $items = []): static;

    public function all(): array;
    public function toArray(): array;
    public function values(): static;
    public function count(): int;
    public function isEmpty(): bool;
    public function isNotEmpty(): bool;
    public function first(): mixed;
    public function last(): mixed;
    public function get(int $index): mixed;

    public function contains(mixed $value): bool;
    public function indexOf(mixed $value): int|string|false;
    public function keys(): array;

    public function map(callable $callback): static;
    public function filter(?callable $callback = null): static;
    public function reduce(callable $callback, mixed $initial = null): mixed;
    public function each(callable $callback): static;

    public function sort(callable $callback): static;
    public function sortDesc(): static;
    public function unique(): static;
    public function reverse(): static;
    public function slice(int $offset, ?int $length = null): static;

    public function merge(CollectionInterface|array $items): static;
    public function diff(CollectionInterface|array $items): static;
    public function intersect(CollectionInterface|array $items): static;

    public function pluck(string $key): array;
    public function groupBy(callable|string $key): array;
    public function keyBy(callable|string $key): static;

    public function implode(string $glue = ','): string;
    public function chunk(int $size): array;
    public function random(): mixed;
    public function shuffle(): static;

    public function add(mixed $item): void;
    public function push(mixed ...$items): void;
    public function pop(): mixed;
    public function shift(): mixed;
    public function unshift(mixed ...$items): void;

    public function search(callable $callback): mixed;
    public function every(callable $callback): bool;
    public function some(callable $callback): bool;

    public function tap(callable $callback): static;
    public function pipe(callable $callback): mixed;
}
