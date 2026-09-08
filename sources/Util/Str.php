<?php

declare(strict_types=1);

namespace Arris\Util;

/**
 * Immutable fluent string.
 *
 * All transformers (lower, upper, trim, append, ...) return a NEW instance
 * and leave the original untouched. The only exception: limit()/words()
 * return $this when the string does not change.
 */
class Str implements StrInterface
{
    private string $string;

    public function __construct(string $string = '')
    {
        $this->string = $string;
    }

    private function newInstance(string $string): static
    {
        return new static($string);
    }

    public static function of(string $string = ''): static
    {
        return new static($string);
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function toString(): string
    {
        return $this->string;
    }

    public function length(): int
    {
        return mb_strlen($this->string);
    }

    public function lower(): static
    {
        return $this->newInstance(mb_strtolower($this->string));
    }

    public function upper(): static
    {
        return $this->newInstance(mb_strtoupper($this->string));
    }

    public function ucfirst(): static
    {
        return $this->newInstance(mb_strtoupper(mb_substr($this->string, 0, 1)) . mb_substr($this->string, 1));
    }

    public function lcfirst(): static
    {
        return $this->newInstance(mb_strtolower(mb_substr($this->string, 0, 1)) . mb_substr($this->string, 1));
    }

    public function substr(int $start, ?int $length = null): static
    {
        return $this->newInstance(mb_substr($this->string, $start, $length));
    }

    public function replace(string $search, string $replace): static
    {
        return $this->newInstance(str_replace($search, $replace, $this->string));
    }

    public function replaceRegex(string $pattern, string $replacement): static
    {
        return $this->newInstance(preg_replace($pattern, $replacement, $this->string));
    }

    public function trim(string $characters = " \t\n\r\0\x0B"): static
    {
        return $this->newInstance(trim($this->string, $characters));
    }

    public function trimLeft(string $characters = " \t\n\r\0\x0B"): static
    {
        return $this->newInstance(ltrim($this->string, $characters));
    }

    public function trimRight(string $characters = " \t\n\r\0\x0B"): static
    {
        return $this->newInstance(rtrim($this->string, $characters));
    }

    public function contains(string $needle): bool
    {
        return mb_strpos($this->string, $needle) !== false;
    }

    public function containsAll(array $needles): bool
    {
        foreach ($needles as $needle) {
            if (!str_contains($this->string, $needle)) {
                return false;
            }
        }
        return true;
    }

    public function containsAny(array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($this->string, $needle)) {
                return true;
            }
        }
        return false;
    }

    public function startsWith(string $needle): bool
    {
        return str_starts_with($this->string, $needle);
    }

    public function endsWith(string $needle): bool
    {
        return str_ends_with($this->string, $needle);
    }

    public function after(string $delimiter): static
    {
        $pos = mb_strpos($this->string, $delimiter);

        if ($pos === false) {
            return $this->newInstance($this->string);
        }

        return $this->newInstance(mb_substr($this->string, $pos + mb_strlen($delimiter)));
    }

    public function afterLast(string $delimiter): static
    {
        $pos = mb_strrpos($this->string, $delimiter);

        if ($pos === false) {
            return $this->newInstance($this->string);
        }

        return $this->newInstance(mb_substr($this->string, $pos + mb_strlen($delimiter)));
    }

    public function before(string $delimiter): static
    {
        $pos = mb_strpos($this->string, $delimiter);

        if ($pos === false) {
            return $this->newInstance($this->string);
        }

        return $this->newInstance(mb_substr($this->string, 0, $pos));
    }

    public function beforeLast(string $delimiter): static
    {
        $pos = mb_strrpos($this->string, $delimiter);

        if ($pos === false) {
            return $this->newInstance($this->string);
        }

        return $this->newInstance(mb_substr($this->string, 0, $pos));
    }

    public function match(string $pattern): ?string
    {
        if (preg_match($pattern, $this->string, $matches)) {
            return $matches[0];
        }

        return null;
    }

    public function matchAll(string $pattern): array
    {
        preg_match_all($pattern, $this->string, $matches);
        return $matches[0] ?? [];
    }

    public function padLeft(int $length, string $pad = ' '): static
    {
        return $this->newInstance(str_pad($this->string, $length, $pad, STR_PAD_LEFT));
    }

    public function padRight(int $length, string $pad = ' '): static
    {
        return $this->newInstance(str_pad($this->string, $length, $pad, STR_PAD_RIGHT));
    }

    public function padBoth(int $length, string $pad = ' '): static
    {
        return $this->newInstance(str_pad($this->string, $length, $pad, STR_PAD_BOTH));
    }

    public function repeat(int $times): static
    {
        return $this->newInstance(str_repeat($this->string, $times));
    }

    public function reverse(): static
    {
        return $this->newInstance(implode('', array_reverse(mb_str_split($this->string))));
    }

    public function shuffle(): static
    {
        $chars = mb_str_split($this->string);
        shuffle($chars);
        return $this->newInstance(implode('', $chars));
    }

    public function slug(string $separator = '-'): static
    {
        $string = mb_strtolower($this->string);
        $string = preg_replace('/[^\w\x{0600}-\x{06FF}\x{0400}-\x{04FF}a-z0-9-]/u', $separator, $string);
        $string = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $string);
        return $this->newInstance(trim($string, $separator));
    }

    /**
     * Ограничивает строку по ширине.
     * Если строка уже короче лимита — возвращает $this (без изменений).
     */
    public function limit(int $limit = 100, string $end = '...'): static
    {
        if (mb_strwidth($this->string, 'UTF-8') <= $limit) {
            return $this;
        }

        return $this->newInstance(rtrim(mb_strimwidth($this->string, 0, $limit, '', 'UTF-8')) . $end);
    }

    /**
     * Ограничивает строку количеством слов.
     * Если слова уже помещаются — возвращает $this (без изменений).
     */
    public function words(int $words = 100, string $end = '...'): static
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $this->string, $matches);

        if (!isset($matches[0]) || mb_strlen($this->string) === mb_strlen($matches[0])) {
            return $this;
        }

        return $this->newInstance(rtrim($matches[0]) . $end);
    }

    public function isEmpty(): bool
    {
        return $this->string === '';
    }

    public function isNotEmpty(): bool
    {
        return $this->string !== '';
    }

    public function isBlank(): bool
    {
        return trim($this->string) === '';
    }

    public function append(string $string): static
    {
        return $this->newInstance($this->string . $string);
    }

    public function prepend(string $string): static
    {
        return $this->newInstance($string . $this->string);
    }

    public function explode(string $separator = ' '): array
    {
        return explode($separator, $this->string);
    }

    public function split(string $pattern = '/\s+/', int $flags = PREG_SPLIT_NO_EMPTY): array
    {
        return preg_split($pattern, $this->string, -1, $flags);
    }

    public function jsonSerialize(): mixed
    {
        return $this->string;
    }
}
