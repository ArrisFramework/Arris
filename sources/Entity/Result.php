<?php

namespace Arris\Entity;

class Result implements \ArrayAccess, \Serializable
{
    /**
     * @var bool
     */
    public bool $is_success = true;

    /**
     * @var bool
     */
    public bool $is_error = false;

    /**
     * @var string
     */
    public string $message = '';

    /**
     * @var string
     */
    public string $code = '';

    /**
     * @var array
     */
    public array $messages = [];

    /**
     * @var array
     */
    public array $data = [];

    public function __construct(bool $is_success = true)
    {
        $this->is_success = $is_success;
        $this->is_error = !$is_success;
    }

    /**
     * Устанавливаем произвольный property
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value): Result
    {
        return $this->__set($key, $value);
    }

    /**
     * Устанавливаем данные
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data = []): Result
    {
        foreach ($data as $k => $v) {
            $this->__setData($k, $v);
        }

        return $this;
    }

    /**
     * Устанавливает цифровой код (может использоваться как код ошибки)
     *
     * @param $code string|int
     * @return $this
     */
    public function setCode($code):Result
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Устанавливаем единичное сообщение
     *
     * @param string $message
     * @param ...$args
     * @return $this
     */
    public function setMessage(string $message = '', ...$args): Result
    {
        if (!empty($args[0])) {
            $this->message = \vsprintf($message, $args[0]);
        } else {
            $this->message = $message;
        }

        /*if (func_num_args() > 1) {
            $this->message = vsprintf($message, $args);
        } else {
            $this->message = $message;
        }*/

        return $this;
    }

    /**
     * Добавляем сообщение к массиву сообщений
     *
     * @param string $message
     * @param ...$args
     * @return $this
     */
    public function addMessage(string $message, ...$args)
    {
        if (func_num_args() > 1) {
            $this->messages[] = \vsprintf($message, $args);
        } else {
            $this->messages[] = $message;
        }

        return $this;
    }

    /**
     * Устанавливает признак: результат УСПЕШЕН
     *
     * @return $this
     */
    public function success(string $message = '', ...$args): Result
    {
        $this->is_success = true;
        $this->is_error = false;

        $this->setMessage($message, $args);

        return $this;
    }

    /**
     * Устанавливает признак: результат ОШИБОЧЕН
     *
     * @return $this
     */
    public function error(string $message = '', ...$args): Result
    {
        $this->is_success = false;
        $this->is_error = true;

        $this->setMessage($message, $args);

        return $this;
    }

    /* === Getters === */

    /**
     * @return string
     */
    public function getMessage():string
    {
        return $this->message;
    }

    /**
     * @param bool $implode
     * @param string $glue
     * @param array $brackets
     * @return array|string
     */
    public function getMessages(bool $implode = false, string $glue = ',', array $brackets = ['[', ']'])
    {
        if ($implode === false) {
            return $this->messages;
        }

        $imploded = \implode($glue, $this->messages);

        if (!empty($brackets)) {
            switch (\count($brackets)) {
                case 0: {
                    $brackets[1] = $brackets[0] = '';
                    break;
                }
                case 1: {
                    $brackets[1] = $brackets[0];
                    break;
                }
            }

            $imploded = $brackets[0] . $imploded . $brackets[1];
        }

        return $imploded;
    }

    /**
     * Возвращает код
     *
     * @return string|int
     */
    public function getCode()
    {
        return $this->code;
    }

    public function getData(): array
    {
        return $this->data;
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
        if (\property_exists($this, $key)) {
            return $this->{$key};
        } elseif (\array_key_exists($key, $this->data)) {
            return $this->data[$key];
        } else {
            return null;
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
     * @return self
     */
    public function __set(string $key, $value = null): self
    {
        $this->{$key} = $value;

        return $this;
    }

    public function __setData(string $key, $value = null): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return \property_exists($this, $offset) || \array_key_exists($offset, $this->data);
    }

    /**
     * @todo: переделать?
     *
     * @param $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        /*if (property_exists($this, $offset)) {
            return $this->{$offset};
        }*/
        return $this->__get($offset);
    }

    /**
     * @todo: переделать?
     *
     * @param $offset
     * @param $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    /**
     * @todo: переделать?
     *
     * @param $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }

    /**
     * Сериализатор
     *
     * @return false|string|null
     */
    public function serialize()
    {
        return \json_encode([
            'is_success'    =>  $this->is_success,
            'is_error'      =>  $this->is_error,
            'message'       =>  $this->message,
            'messages'      =>  $this->messages,
            'code'          =>  $this->code,
            'data'          =>  $this->data
        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE /*| JSON_THROW_ON_ERROR*/);
    }

    /**
     * Десериализатор
     *
     * @param $data
     * @return $this
     */
    public function unserialize($data): Result
    {
        $json = \json_decode($data, true);
        $this->is_success   = array_key_exists('is_success', $json) ? $json['is_success'] : true;
        $this->is_error     = array_key_exists('is_error', $json) ? $json['is_error'] : false;
        $this->message      = array_key_exists('message', $json) ? $json['message'] : '';
        $this->code         = array_key_exists('code', $json) ? $json['code'] : '';
        $this->data         = array_key_exists('data', $json) ? $json['data'] : '';
        $this->messages     = array_key_exists('messages', $json) ? $json['messages'] : '';
        unset($json);

        return $this;
    }
}