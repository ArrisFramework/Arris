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
     * Validates whether stack is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    public function count(): int
    {
        return count($this->stack);
    }

    /**
     * Clear stack entirely.
     *
     * @return void
     */
    public function clear(): void
    {
        unset($this->stack);
        $this->stack = [];
    }

    /**
     * Get all of the values inside the stack.
     *
     * @return array
     */
    public function get(): array
    {
        return $this->stack;
    }

    public function toArray():array
    {
        return array_reverse($this->stack);
    }

    public function implode($separator = '', $inverse_order = false):string
    {
        return $inverse_order ? implode($separator, array_reverse($this->stack)) : implode($separator, $this->stack);
    }

}

# -eof-