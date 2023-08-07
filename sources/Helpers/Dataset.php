<?php

namespace Arris\Helpers;

use ReflectionException;
use ReflectionFunction;

class Dataset
{
    /**
     * https://www.php.net/manual/en/reflectionfunctionabstract.isclosure.php
     *
     * @throws ReflectionException
     */
    public static function is_closure($suspected_closure)
    {
        $reflection = new ReflectionFunction($suspected_closure);

        return (bool)$reflection->isClosure();
    }

    /**
     * Замена mapDataset, поддерживает расширенный синтаксис и коллбэки для обработки параметров
     *
     * Практически, не метод фильтрации исходного массива, а метод генерации нового датасета на основе исходного
     *
     * Структура правила:
     * KEY - имя правила
     * - source - из какого поля исходного массива брать данные? Если отсутствует - совпадает с ключом
     * - target - в какое поле целевого массива записать данные? Если отсутствует - совпадает с ключом
     * - default - значение поля в целевом массиве, если в исходном оно не найдено. Если не задано, то null (переопределимо 3 аргументом).
     * - processor - возвращаемый результат ИЛИ closure - функция, получающая два значения (исходное и исходный массив) и возвращающая значение
     * - type - приведение типа (int, str, string, bool, float ...)
     *
     * Теперь о нюансах:
     * 1) processor:
     * а) - НЕ Closure - в результирующий массив передается его значение (которому может быть задан тип)
     * б) - Closure - передается результат функции
     * 2) чтобы просто скопировать ключ:значение из исходного массива, достаточно указать пустое правило
     *    для ключа ( например, `'mode' => []` )
     * 3) результирующий массив содержит ТЕ И ТОЛЬКО ТЕ ключи, которые указаны в списке ПРАВИЛ.
     * 4) если в исходном массиве нет ключа XXX, то в результирующем будет XXX => null (по умолчанию)
     *
     * Прежнее поведение: если в processor строка, то она интерпретируется как допустимая функция - удалено.
     * Это работало только если функция принимала два аргумента (строка и массив) и давало малопредсказуемые результаты
     *
     * @param $source
     * @param $rules
     * @param null $default_undefined_value
     * @return array
     * @throws ReflectionException
     */
    public static function map($source, $rules, $default_undefined_value = null): array
    {
        $dataset = [];

        array_walk($rules, static function($rule, $index) use ($default_undefined_value, &$dataset, $source) {
            $_src_key = array_key_exists('source', $rule) ? $rule['source'] : $index;
            $_dst_key = array_key_exists('target', $rule) ? $rule['target'] : $index;
            $_processor = array_key_exists('processor', $rule) ? $rule['processor'] : null;

            $result = $default_undefined_value;

            if (!empty($source[ $_src_key ])) {
                if ($_processor) {
                    $_processor_type = gettype($_processor);

                    if ($_processor_type === 'object' && self::is_closure($_processor)) {
                        // Если здесь указать ключи массива, то в PHP8+ имена параметров в вызываемом коллбэке
                        // ДОЛЖНЫ, ДОЛЖНЫ СУКА СОВПАДАТЬ С КЛЮЧАМИ, ИНАЧЕ СУКА ВЫЛЕТИТ FATAL EГГОГ.
                        // Поэтому как бы красиво это ни было, я их не указываю!
                        $result = call_user_func_array($_processor, [ /*'source_key' =>*/ $source[ $_src_key ], /*'source_dataset' =>*/ $source ]);
                    } else {
                        $result = $_processor;
                    }
                } else {
                    $result = $source[ $_src_key ];
                }
            } else {
                if (array_key_exists('default', $rule)) {
                    $result = $rule['default'];
                }/* else {
                    $result = $default_undefined_value;
                }*/
            }

            if (array_key_exists('type', $rule)) {
                $allowed_types = ['int', 'integer', 'bool', 'boolean', 'float', 'double', 'str', 'string', 'array', 'object'];
                $type = $rule['type'];
                if ($type === 'str') $type = 'string';

                if (in_array($type, $allowed_types)) {
                    settype($result, $type);
                }
            }

            $dataset[ $_dst_key ] = $result;
        });
        return $dataset;
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
        array_map(static function ($row) use (&$result, $column_id){
            $result[ $row[ $column_id ] ] = $row;
        }, $dataset);
        return $result;
    }

}