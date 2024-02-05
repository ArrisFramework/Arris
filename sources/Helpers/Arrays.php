<?php

namespace Arris\Helpers;

class Arrays
{
    /**
     * Разбивает строку по $separator и возвращает массив значений, приведенных к INTEGER
     *
     * @param string $string
     * @param string $separator
     * @return array
     */
    public static function explodeToInt(string $string, string $separator = ' '):array
    {
        return array_map(static function ($i) use ($separator) { return (int)$i; }, explode($separator, $string));
    }

    /**
     * Разбивает строку по $separator и возвращает массив значений, приведенных к типу.
     *
     * Список возможных типов: 'int', 'integer', 'bool', 'boolean', 'float', 'double', 'str', 'string', 'array', 'object'
     */
    public static function explodeToType(string $string, string $separator = ' ', $callback = '')
    {
        return
            array_map( static function ($i) use ($separator, $callback)
                {
                    if (empty($callback)) {
                        return $i;
                    }

                    if (is_string($callback)) {
                        $allowed_types = ['int', 'integer', 'bool', 'boolean', 'float', 'double', 'str', 'string', 'array', 'object'];
                        $type = $callback;
                        if ($type === 'str') $type = 'string';

                        if (in_array($callback, $allowed_types)) {
                            settype($i, $type);
                        }

                        return $i; // anyway return $i
                    }

                    if (Dataset::is_closure($callback)) {
                        return \call_user_func($callback, $i);
                    }

                    return $i;

                }, explode($separator, $string)
            );

    }

}

# -eof-