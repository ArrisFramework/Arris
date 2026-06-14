<?php

namespace Arris\Helpers;

interface ArraysInterface
{
    /**
     * Обёртка над array_filter.
     *
     * @param array    $input         Входной массив
     * @param callable|null $callback Функция фильтрации
     * @param int $flag               Флаг режима (ARRAY_FILTER_USE_KEY, ARRAY_FILTER_USE_BOTH)
     *
     * @return array
     */
    public static function filter(array $input, callable $callback = null, int $flag = 0): array;

    /**
     * Разбивает строку и приводит каждый элемент к указанному типу.
     *
     * @param string                       $string         Исходная строка
     * @param string                       $separator      Разделитель
     * @param string|callable|array|null   $typeOrCallback Тип ('int','float','bool','string') или callback
     * @return array
     */
    public static function explodeToType(
        string $string,
        string $separator = ' ',
        string|callable|array|null $typeOrCallback = null
    ): array;

    /**
     * Безопасно получает значение из массива по ключу с валидацией разрешённых значений.
     *
     * @param array      $inputArray    Входной массив
     * @param string|int $requiredKey   Ключ для поиска
     * @param array      $allowedValues Разрешённые значения
     * @param mixed      $defaultValue  Значение по умолчанию
     * @return mixed
     */
    public static function filterArrayForAllowed(
        array $inputArray,
        string|int $requiredKey,
        array $allowedValues,
        mixed $defaultValue
    ): mixed;

    /**
     * Переиндексирует массив ассоциативных массивов по значению указанной колонки.
     *
     * @param array      $dataset    Исходный массив строк (FETCH_ASSOC)
     * @param string|int $column_id  Имя колонки для индексации
     * @return array
     */
    public static function groupDatasetByColumn(array $dataset, $column_id): array;
}