<?php

namespace Arris\Database;

final class Context
{
    public array $parameters = [];

    /**
     * Bind parameter=>value and return its name (p0 ... pN)
     *
     * @param $value
     * @return string
     */
    public function parameter($value): string
    {
        $name = 'p' . count($this->parameters);
        $this->parameters[ $name ] = $value;
        return ":{$name}";
    }

    /**
     * @return array
     */
    public function context():array
    {
        return $this->parameters;
    }
}