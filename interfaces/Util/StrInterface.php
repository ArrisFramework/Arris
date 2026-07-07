<?php

declare(strict_types=1);

namespace Arris\Util;

use JsonSerializable;
use Stringable;

interface StrInterface extends Stringable, JsonSerializable
{
    public static function of(string $string = ''): static;

    public function __toString(): string;
    public function toString(): string;

    public function length(): int;

    public function lower(): static;
    public function upper(): static;
    public function ucfirst(): static;
    public function lcfirst(): static;

    public function substr(int $start, ?int $length = null): static;
    public function replace(string $search, string $replace): static;
    public function replaceRegex(string $pattern, string $replacement): static;

    public function trim(string $characters = " \t\n\r\0\x0B"): static;
    public function trimLeft(string $characters = " \t\n\r\0\x0B"): static;
    public function trimRight(string $characters = " \t\n\r\0\x0B"): static;

    public function contains(string $needle): bool;
    public function containsAll(array $needles): bool;
    public function containsAny(array $needles): bool;
    public function startsWith(string $needle): bool;
    public function endsWith(string $needle): bool;

    public function after(string $delimiter): static;
    public function afterLast(string $delimiter): static;
    public function before(string $delimiter): static;
    public function beforeLast(string $delimiter): static;

    public function match(string $pattern): ?string;
    public function matchAll(string $pattern): array;

    public function padLeft(int $length, string $pad = ' '): static;
    public function padRight(int $length, string $pad = ' '): static;
    public function padBoth(int $length, string $pad = ' '): static;

    public function repeat(int $times): static;
    public function reverse(): static;
    public function shuffle(): static;

    public function slug(string $separator = '-'): static;
    public function limit(int $limit = 100, string $end = '...'): static;
    public function words(int $words = 100, string $end = '...'): static;

    public function isEmpty(): bool;
    public function isNotEmpty(): bool;
    public function isBlank(): bool;

    public function append(string $string): static;
    public function prepend(string $string): static;

    public function explode(string $separator = ' '): array;
    public function split(string $pattern = '/' . PREG_SPLIT_NO_EMPTY): array;

    public function jsonSerialize(): mixed;
}
