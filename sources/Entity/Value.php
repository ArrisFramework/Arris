<?php

namespace Arris\Entity;

class Value
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function raw()
    {
        return $this->value;
    }

    public function get()
    {
        return $this->value;
    }

    public function __toString():string
    {
        return (string)$this->value;
    }

    public function toInt():int
    {
        return (int)$this->value;
    }

    public function toString():string
    {
        return (string)$this->value;
    }

    public function toBool():bool
    {
        return (bool)$this->value;
    }

    public function toArray():array
    {
        return (array)$this->value;
    }

}