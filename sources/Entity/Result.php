<?php

namespace Arris\Entity;

/**
 * Как это использовать?
 *
 * В теле метода сказать:
 * return new Result([
'query'     =>  $query,
'last'      =>  $last,
'redis_key' =>  $redis_key,
'page'      =>  $page
]);
 * Это создаст экземпляр класса Result с полями query, last, redis_key, page соответственно.
 *
 * Они будут доступны как через хелпер:
 * $redis_key = $result->get('redis_key')->toString();
 *
 * так и напрямую:
 * $query = $result['query'];
 *
 * Зачем делать доступность через ArrayAccess?
 * Это позволяет передавать ТИП возвращаемого элемента.
 *
 * Для этого в шапке метода, возвращающего Result нужно указать массив с перечислением типов:
 * Это используется PHPStan-нотация: https://stackoverflow.com/a/61369750
 *
 * @return array{query: Select, last: bool, redis_key: string, page: int}
 *
 * Впрочем, можно указать тип и принудительно, уже по месту использования: "@ var Select $query"
 *
 */
class Result implements \ArrayAccess
{
    public $result;

    public $is_error = false;

    public $error;

    public $error_code;

    public $repository = [];

    public function __construct($dataset = [])
    {
        $this->is_error = false;
        $this->error_code = 0;

        foreach ($dataset as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function isError():bool
    {
        return $this->is_error;
    }

    public function getErrorCode()
    {
        return $this->error_code;
    }

    public function getError()
    {
        return $this->error;
    }

    public function __set($key, $data)
    {
        $this->{$key} = $data;
    }

    public function set($key, $data)
    {
        $this->result = $data;
    }

    public function __get($key)
    {
        return $this->{$key};
    }

    /**
     * Немного сложноЭ
     * Наверное, возврат экземпляра Value - лишнее
     *
     * @return array<Value>|Value
     */
    public function get()
    {
        $args = func_get_args();

        if (empty($args)) {
            return new Value($this->result);
        } else {
            if (count($args) == 1) {
                if (is_object($this->{$args[0]}) || is_callable($this->{$args[0]}) || is_array($this->{$args[0]})) {
                    return $this->{$args[0]};
                } else {
                    return new Value($this->{$args[0]});
                }
            } else {
                $set = [];
                foreach ($args as $name) {
                    $set[] = $this->{$name};
                }
                return $set;
            }
        }
    }

    /*public function exception(\Throwable $exception)
    {
        $this->is_error = true;
        $this->error_code = $exception->getCode();
        $this->error = $exception->getMessage();
    }*/

    /** ArrayAccess */

    public function offsetExists($offset)
    {
        return !empty($this->{$offset});
    }

    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }
}