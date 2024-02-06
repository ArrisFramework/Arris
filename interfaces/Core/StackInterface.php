<?php

namespace Arris\Core;

/**
 * Class Stack
 * @package Arris\Utils
 *
 * Примитивный стэк, правильнее использовать https://github.com/php-ds/polyfill/blob/master/src/Stack.php
 * из пакета `php-ds/php-ds`
 */
interface StackInterface
{
    public function __construct($values = null, $limit = null);

    /**
     * Определяет поведение при извлечении данных из пустого стэка.
     * Если вызвано с первым параметром TRUE - извлечение данных вернет значение по умолчанию
     *
     * @param bool $allow
     * @param $default_null_value
     * @return void
     */
    public function allowPopFromEmptyStack(bool $allow = true, $default_null_value = '');

    /**
     * Push an item to the stack.
     *
     * @param mixed ...$items
     */
    public function push(...$items);

    /**
     * Pop last value from stack.
     *
     * @return mixed
     */
    public function pop();

    /**
     * Проверяет, пуст ли стэк
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Возвращает количество элементов в стэке
     *
     * @return int
     */
    public function count(): int;

    /**
     * Очищает стэк
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Возвращает все элементы стэка в прямом порядке
     *
     * @return array
     */
    public function get(): array;

    /**
     * Инвертирует стэк. И это всё еще тот же самый стэк.
     *
     * @return Stack
     */
    public function reverse();

    /**
     * Возвращает стэк как массив (в реверсном порядке)
     *
     * @return array
     */
    public function getReversed():array;

    /**
     * Alias of getReversed()
     *
     * @return array
     */
    public function toArray():array;

    /**
     * Склеивает содержимое стэка в строчки. Работает только если стэк содержит строки, а не структуры данных
     *
     * @param string $separator
     * @param bool $inverse_order
     * @return string
     */
    public function implode(string $separator = '', bool $inverse_order = false):string;

}