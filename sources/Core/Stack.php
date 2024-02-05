<?php

namespace Arris\Core;

use RuntimeException;

/**
 * Class Stack
 * @package Arris\Utils
 *
 * Примитивный стэк, правильнее использовать https://github.com/php-ds/polyfill/blob/master/src/Stack.php
 * из пакета `php-ds/php-ds`
 */

class Stack
{
    /**
     * @var int
     */
    private $limit;

    /**
     * @var array
     */
    private array $stack;

    public function __construct($values = null, $limit = null)
    {
        // stack can only contain this many items
        $this->limit = $limit;

        // initialize the stack
        $this->stack = [];

        if (is_null($values)) {
            $values = [];
        } else if (!is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $value) {
            $this->push($value);
        }
    }

    /**
     * Push an item to the stack.
     *
     * @param mixed ...$items
     */
    public function push(...$items)
    {
        // trap for stack overflow
        if (!is_null($this->limit) && ($this->count() >= $this->limit)) {
            throw new RunTimeException('Stack is full!');
        }

        foreach ($items as $i) {
            array_push($this->stack, $i);
        }
    }

    /**
     * Pop last value from stack.
     *
     * @return mixed
     */
    public function pop()
    {
        if ($this->count() === 0) {
            throw new RuntimeException('Stack is empty');
        }

        return array_pop($this->stack);
    }

    /**
     * Проверяет, пуст ли стэк
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    /**
     * Возвращает количество элементов в стэке
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->stack);
    }

    /**
     * Очищает стэк
     *
     * @return void
     */
    public function clear(): void
    {
        unset($this->stack);
        $this->stack = [];
    }

    /**
     * Возвращает все элементы стэка
     *
     * @return array
     */
    public function get(): array
    {
        return $this->stack;
    }

    /**
     * ?
     *
     * @return array
     */
    public function toArray():array
    {
        return array_reverse($this->stack);
    }

    /**
     * Склеивает содержимое стэка в строчки. Работает только если стэк содержит строки, а не структуры данных
     *
     * @param string $separator
     * @param bool $inverse_order
     * @return string
     */
    public function implode(string $separator = '', bool $inverse_order = false):string
    {
        return $inverse_order ? implode($separator, array_reverse($this->stack)) : implode($separator, $this->stack);
    }

    /**
     * Инвертирует стэк
     *
     * @return Stack
     */
    public function reverse()
    {
        $data = [];
        do {
            $data[] = $this->pop();
        } while (!$this->isEmpty());

        foreach ($data as $value) $this->push($value);

        return $this;
    }

}

# -eof-