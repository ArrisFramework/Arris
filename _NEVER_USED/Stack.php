<?php

namespace Arris\Core;

use RuntimeException;

class Stack implements StackInterface
{
    /**
     * @var int
     */
    private $limit;

    /**
     * @var array
     */
    private array $stack;
    
    private bool $allowPopFromEmptyStack = false;
    
    /**
     * Дефолтное значение, извлекаемое из пустого стэка, если это разрешено
     * @var mixed
     */
    private $defaultValueForEmptyStack = null;

    public function __construct($values = null, $limit = null)
    {
        // stack can only contain this many items
        $this->limit = $limit;

        // initialize the stack
        $this->stack = [];

        if (\is_null($values)) {
            $values = [];
        } else if (!\is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $value) {
            $this->push($value);
        }
    }

    public function allowPopFromEmptyStack(bool $allow = true, $default_null_value = '')
    {
        $this->allowPopFromEmptyStack = $allow;
        $this->defaultValueForEmptyStack = $default_null_value;
    }

    public function push(...$items)
    {
        // trap for stack overflow
        if (!\is_null($this->limit) && ($this->count() >= $this->limit)) {
            throw new RunTimeException('Stack is full!');
        }

        foreach ($items as $i) {
            $this->stack[] = $i;
        }
    }

    public function pop()
    {
        if ($this->count() === 0) {
            if ($this->allowPopFromEmptyStack) {
                return $this->defaultValueForEmptyStack;
            }
            
            throw new RuntimeException('Stack is empty');
        }

        return \array_pop($this->stack);
    }

    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    public function count(): int
    {
        return \count($this->stack);
    }

    public function clear(): void
    {
        unset($this->stack);
        $this->stack = [];
    }

    public function get(): array
    {
        return $this->stack;
    }

    public function getReversed():array
    {
        return array_reverse($this->stack);
    }

    public function toArray():array
    {
        return self::getReversed();
    }

    public function implode(string $separator = '', bool $inverse_order = false):string
    {
        return $inverse_order ? implode($separator, array_reverse($this->stack)) : implode($separator, $this->stack);
    }

    public function reverse()
    {
        $data = [];
        do {
            $data[] = $this->pop();
        } while (!$this->isEmpty());

        foreach ($data as $value) $this->push($value);
        
        unset($data);

        return $this;
    }

}

# -eof-