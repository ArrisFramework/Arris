<?php

namespace Arris\Util;

use JsonSerializable;

/**
 * Fluent-обёртка над рекурсивным слиянием массивов. Две стратегии: `patch` (replace) и `merge` (append),
 * поддержка удаления ключей через `null`, кеширование результата и снепшоты через `save()`.
 *
 * NB: Vibe-coded
 */
class ArrayComposer implements JsonSerializable, ArrayComposerInterface
{
    private array $data;
    private bool $nullUnsetKeys;
    private bool $isDirty = false;
    private ?array $compiled = null;

    public function __construct(array $original, bool $nullUnsetKeys = false)
    {
        $this->data = $original;
        $this->nullUnsetKeys = $nullUnsetKeys;
    }

    /**
     * Слияние с заменой значений (аналог array_replace_recursive)
     */
    public function patch(array ...$arrays): self
    {
        foreach ($arrays as $array) {
            $this->data = $this->mergeRecursiveReplace($this->data, $array);
        }
        $this->invalidate();
        return $this;
    }

    /**
     * Слияние без замены (аналог array_merge_recursive)
     */
    public function merge(array ...$arrays): self
    {
        foreach ($arrays as $array) {
            $this->data = array_merge_recursive($this->data, $array);
        }
        $this->invalidate();
        return $this;
    }

    /**
     * "Компилирует" результат в массив и возвращает новый инстанс
     */
    public function save(): self
    {
        $compiled = $this->toArray();
        return new self($compiled, $this->nullUnsetKeys);
    }

    /**
     * Возвращает результирующий массив
     */
    public function toArray(): array
    {
        if ($this->compiled === null || $this->isDirty) {
            $this->compiled = $this->data;
            $this->isDirty = false;
        }
        return $this->compiled;
    }

    /**
     * Алиас для toArray()
     */
    public function asArray(): array
    {
        return $this->toArray();
    }

    /**
     * Магический метод для приведения к массиву
     */
    public function __toArray(): array
    {
        return $this->toArray();
    }

    /**
     * Для json_encode
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Магический метод для строкового представления
     */
    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Рекурсивное слияние с заменой значений
     */
    private function mergeRecursiveReplace(array $first, array $second): array
    {
        foreach ($second as $key => $value) {
            if ($this->nullUnsetKeys && $value === null) {
                unset($first[$key]);
            } elseif (is_array($value) && isset($first[$key]) && is_array($first[$key])) {
                $first[$key] = $this->mergeRecursiveReplace($first[$key], $value);
            } else {
                $first[$key] = $value;
            }
        }

        return $first;
    }

    /**
     * Сбрасывает кеш скомпилированного результата
     */
    private function invalidate(): void
    {
        $this->isDirty = true;
    }
}