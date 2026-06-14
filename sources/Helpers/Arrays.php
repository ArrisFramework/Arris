<?php

namespace Arris\Helpers;

use InvalidArgumentException;

class Arrays implements ArraysInterface
{
    /**
     * Обёртка над filter
     *
     * @param array $input
     * @param $callback
     * @param $flag
     *
     * @return array
     */
    public static function filter(array $input, $callback = null, $flag = 0): array
    {
        return \array_filter($input, $callback, $flag);
    }

    /**
     * Разбивает строку и приводит каждый элемент к указанному типу.
     *
     * Примеры:
     *  - explodeToType('1 2 3', ' ', 'int')           => [1, 2, 3]
     *  - explodeToType('1.5,2.7', ',', 'float')       => [1.5, 2.7]
     *  - explodeToType('a,b,c', ',', fn($v) => strtoupper($v)) => ['A', 'B', 'C']
     *
     * @param string $string Исходная строка
     * @param string $separator Разделитель
     * @param string|callable|array|null $typeOrCallback Тип ('int', 'float', 'bool', 'string') или callback
     *
     * @return array Массив обработанных значений
     * @throws InvalidArgumentException Если указан неизвестный тип
     */
    public static function explodeToType(
        string $string,
        string $separator = ' ',
        string|callable|array|null $typeOrCallback = null
    ): array {
        $items = explode($separator, $string);

        // Если тип не указан — возвращаем как есть
        if ($typeOrCallback === null) {
            return $items;
        }

        // Если передан массив типов — применяем каждый к своему элементу
        if (is_array($typeOrCallback)) {
            return array_map(
                fn($item, $type) => is_callable($type)
                    ? $type($item)
                    : Dataset::castToType($item, $type),
                $items,
                $typeOrCallback
            );
        }

        // Если передан callable (Closure, функция, метод класса)
        if (is_callable($typeOrCallback)) {
            return array_map($typeOrCallback, $items);
        }

        // Если передана строка — приводим к типу
        return array_map(
            fn($item) => Dataset::castToType($item, $typeOrCallback),
            $items
        );
    }



    /**
     * Безопасно получает значение из массива по ключу с валидацией разрешенных значений.
     *
     * Примеры:
     *  - filterArrayForAllowed(['status' => 'active'], 'status', ['active', 'inactive'], 'unknown')
     *    => 'active'
     *  - filterArrayForAllowed(['status' => 'banned'], 'status', ['active', 'inactive'], 'unknown')
     *    => 'unknown'
     *  - filterArrayForAllowed([], 'status', ['active', 'inactive'], 'unknown')
     *    => 'unknown'
     *
     * @param array<mixed> $inputArray Входной массив
     * @param string|int $requiredKey Ключ для поиска
     * @param array<mixed> $allowedValues Разрешенные значения
     * @param mixed $defaultValue Значение по умолчанию
     * @return mixed Значение из массива или дефолт
     */
    public static function filterArrayForAllowed(
        array $inputArray,
        string|int $requiredKey,
        array $allowedValues,
        mixed $defaultValue
    ): mixed {
        // Ранний выход, если ключа нет
        if (!array_key_exists($requiredKey, $inputArray)) {
            return $defaultValue;
        }

        $value = $inputArray[$requiredKey];

        // Строгое сравнение (третий параметр true)
        return in_array($value, $allowedValues, true) ? $value : $defaultValue;
    }

    /**
     * Возвращает новый датасет, индекс для строк которого равен значению колонки строки
     * Предназначен для переформатирования PDO-ответов, полученных в режиме FETCH_ASSOC
     *
     * [ 0 => [ 'id' => 5, 'data' => 10], 1 => [ 'id' => 6, 'data' => 12] .. ]
     * При вызове с аргументами ($dataset, 'id') возвращает
     * [ 5 => [ 'id' => 5, 'data' => 10], 6 => [ 'id' => 6, 'data' => 12] .. ]
     *
     * @param array $dataset
     * @param $column_id
     * @return array
     */
    public static function groupDatasetByColumn(array $dataset, $column_id):array
    {
        $result = [];
        \array_map(static function ($row) use (&$result, $column_id){
            $result[ $row[ $column_id ] ] = $row;
        }, $dataset);
        return $result;
    }

}