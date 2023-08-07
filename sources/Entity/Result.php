<?php

namespace Arris\Entity;

/**
 * Result class:
 *
 * new Result(bool $is_success, string $message, array $params) - common call
 *
 * In all other cases `message` or `is_success` can be overriden with data keys.
 *
 * new Result(string $message, array $params) - is_success will true
 * new Result(bool $result, array $params) - message will empty
 * new Result(bool $result, string $message) - data will empty
 *
 * new Result(array $params) - message will empty, is_success will true
 */
class Result implements \ArrayAccess, \Serializable
{
    /**
     * @var bool|null
     */
    public ?bool $is_success = true;

    /**
     * @var bool|null
     */
    public ?bool $is_error = false;

    /**
     * @var string|null
     */
    public $message = '';

    /**
     * @var array|null
     */
    public $data = [];

    public function __construct()
    {
        $this->is_success = true;
        $this->is_error = false;
        $this->message = '';
        $this->data = [];

        switch (func_num_args()) {
            // new Result(bool $is_success, string $message, array $params)
            case 3: {
                $first = func_get_arg(0);
                $second = func_get_arg(1);
                $third = func_get_arg(2);

                $this->is_success = $first;
                $this->is_error = !$this->is_success;
                $this->message = $second;

                foreach ($third as $k => $v) {
                    $this->__set($k, $v);
                }
                $this->data = $third;

                break;
            }
            // new Result(string $message, array $params)
            // new Result(bool $is_success, array $params)
            case 2: {
                $first = func_get_arg(0);
                $second = func_get_arg(1);

                if (is_bool($first)) {
                    $this->is_success = func_get_arg(0);
                    $this->is_error = !$this->is_success;
                } elseif (is_string($first)) {
                    $this->is_success = null;
                    $this->is_error = null;
                    $this->message = $first;
                }

                if (is_array($second)) {
                    foreach ($second as $k => $v) {
                        $this->__set($k, $v);
                    }
                    $this->data = $second;
                } elseif (is_string($second)) {
                    $this->message = $second;
                }

                break;
            }
            // new Result(array $params)
            default: {
                $first = func_get_arg(0);

                $this->is_success = true;
                $this->is_error = false;
                $this->message = '';
                $this->data = [];

                if (is_array($first)) {
                    foreach ($first as $k => $v) {
                        $this->__set($k, $v);
                    }
                    $this->data = $first;
                }

                break;
            }
        }
    }

    /**
     * Setter
     * Handles access to non-existing property
     *
     * ? если ключ не найден в списке property, то нужно добавить его в массив $data
     *
     * @param string $key
     * @param $value
     * @return void
     */
    public function __set(string $key, $value = null)
    {
        $this->{$key} = $value;
    }

    /**
     * Getter.
     * Handles access to non-existing property
     *
     * ? если ключ не найден в списке property, то нужно проверить его в массиве $data и только потом вернуть null
     *
     * @param string $key
     * @return null
     */
    public function __get(string $key)
    {
        return $this->offsetExists($key) ? $this->{$key} : null;
    }

    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset)
    {
        if (property_exists($this, $offset)) {
            return $this->{$offset};
        }
        return null;
    }

    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }

    /**
     * Stringable interface available since PHP 8.0
     * @return string
     */
    public function __toString(): string
    {
        return $this->message;
    }

    public function serialize()
    {
        return json_encode([
            'is_success'    =>  $this->is_success,
            'is_error'      =>  $this->is_error,
            'message'       =>  $this->message,
            'data'          =>  $this->data
        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE /*| JSON_THROW_ON_ERROR*/);
    }

    public function unserialize($data)
    {
        $json = json_decode($data, true);
        $this->is_success   = array_key_exists('is_success', $json) ? $json['is_success'] : true;
        $this->is_error     = array_key_exists('is_error', $json) ? $json['is_error'] : false;
        $this->message      = array_key_exists('message', $json) ? $json['message'] : '';
        $this->data         = array_key_exists('data', $json) ? $json['data'] : '';
        unset($json);
    }


}