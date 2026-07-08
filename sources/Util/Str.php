<?php

declare(strict_types=1);

namespace Arris\Util;

use Stringable;

class Str implements StrInterface
{
    private string $string;

    public function __construct(string $string = '')
    {
        $this->string = $string;
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
        $this->string = mb_strtolower($this->string);
        return $this;
    }

    public function upper(): static
    {
        $this->string = mb_strtoupper($this->string);
        return $this;
    }

    public function ucfirst(): static
    {
        $this->string = mb_strtoupper(mb_substr($this->string, 0, 1)) . mb_substr($this->string, 1);
        return $this;
    }

    public function lcfirst(): static
    {
        $this->string = mb_strtolower(mb_substr($this->string, 0, 1)) . mb_substr($this->string, 1);
        return $this;
    }

    public function substr(int $start, ?int $length = null): static
    {
        $this->string = mb_substr($this->string, $start, $length);
        return $this;
    }

    public function replace(string $search, string $replace): static
    {
        $this->string = str_replace($search, $replace, $this->string);
        return $this;
    }

    public function replaceRegex(string $pattern, string $replacement): static
    {
        $this->string = preg_replace($pattern, $replacement, $this->string);
        return $this;
    }

    public function trim(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->string = trim($this->string, $characters);
        return $this;
    }

    public function trimLeft(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->string = ltrim($this->string, $characters);
        return $this;
    }

    public function trimRight(string $characters = " \t\n\r\0\x0B"): static
    {
        $this->string = rtrim($this->string, $characters);
        return $this;
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
            return new static($this->string);
        }

        return new static(mb_substr($this->string, $pos + mb_strlen($delimiter)));
    }

    public function afterLast(string $delimiter): static
    {
        $pos = mb_strrpos($this->string, $delimiter);

        if ($pos === false) {
            return new static($this->string);
        }

        return new static(mb_substr($this->string, $pos + mb_strlen($delimiter)));
    }

    public function before(string $delimiter): static
    {
        $pos = mb_strpos($this->string, $delimiter);

        if ($pos === false) {
            return new static($this->string);
        }

        return new static(mb_substr($this->string, 0, $pos));
    }

    public function beforeLast(string $delimiter): static
    {
        $pos = mb_strrpos($this->string, $delimiter);

        if ($pos === false) {
            return new static($this->string);
        }

        return new static(mb_substr($this->string, 0, $pos));
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
        $this->string = str_pad($this->string, $length, $pad, STR_PAD_LEFT);
        return $this;
    }

    public function padRight(int $length, string $pad = ' '): static
    {
        $this->string = str_pad($this->string, $length, $pad, STR_PAD_RIGHT);
        return $this;
    }

    public function padBoth(int $length, string $pad = ' '): static
    {
        $this->string = str_pad($this->string, $length, $pad, STR_PAD_BOTH);
        return $this;
    }

    public function repeat(int $times): static
    {
        $this->string = str_repeat($this->string, $times);
        return $this;
    }

    public function reverse(): static
    {
        $this->string = implode('', array_reverse(mb_str_split($this->string)));
        return $this;
    }

    public function shuffle(): static
    {
        $chars = mb_str_split($this->string);
        shuffle($chars);
        $this->string = implode('', $chars);
        return $this;
    }

    public function slug(string $separator = '-'): static
    {
        $string = mb_strtolower($this->string);
        $string = preg_replace('/[^\w\x{0600}-\x{06FF}\x{0400}-\x{04FF}a-z0-9-]/u', $separator, $string);
        $string = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $string);
        $this->string = trim($string, $separator);
        return $this;
    }

    public function limit(int $limit = 100, string $end = '...'): static
    {
        if (mb_strwidth($this->string, 'UTF-8') <= $limit) {
            return $this;
        }

        $this->string = rtrim(mb_strimwidth($this->string, 0, $limit, '', 'UTF-8')) . $end;
        return $this;
    }

    public function words(int $words = 100, string $end = '...'): static
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $this->string, $matches);

        if (!isset($matches[0]) || mb_strlen($this->string) === mb_strlen($matches[0])) {
            return $this;
        }

        $this->string = rtrim($matches[0]) . $end;
        return $this;
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
        $this->string .= $string;
        return $this;
    }

    public function prepend(string $string): static
    {
        $this->string = $string . $this->string;
        return $this;
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
