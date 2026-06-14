<?php

namespace Arris\Helpers;

use InvalidArgumentException;

class Dataset
{
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
    public static function groupByColumn(array $dataset, $column_id):array
    {
        $result = [];
        \array_map(static function ($row) use (&$result, $column_id) {
            $result[ $row[ $column_id ] ] = $row;
        }, $dataset);
        return $result;
    }

    /**
     * Генерирует новый массив на основе исходного по набору правил.
     *
     * Структура правила:
     *  - source    - ключ исходного массива (по умолчанию = ключ правила)
     *  - target    - ключ целевого массива (по умолчанию = ключ правила)
     *  - default   - значение, если ключ отсутствует в исходном массиве
     *  - processor - callable($value, $source) или фиксированное значение
     *  - type      - приведение типа: 'int', 'float', 'bool', 'string', 'array'
     *
     * Примеры:
     *  - map(['name' => 'John', 'age' => '25'], ['name' => [], 'age' => ['type' => 'int']])
     *    => ['name' => 'John', 'age' => 25]
     *
     *  - map(['price' => 100], [
     *      'price_with_tax' => [
     *          'source' => 'price',
     *          'processor' => fn($v) => $v * 1.2
     *      ]
     *    ])
     *    => ['price_with_tax' => 120.0]
     *
     * @param array<string, mixed> $source Исходный массив
     * @param array<string, array<string, mixed>> $rules Правила маппинга
     * @param mixed $defaultUndefinedValue Значение по умолчанию для отсутствующих ключей
     * @return array<string, mixed> Сформированный массив
     */
    public static function map(
        array $source,
        array $rules,
        mixed $defaultUndefinedValue = null
    ): array {
        $dataset = [];

        foreach ($rules as $ruleKey => $rule) {
            // Нормализация правила (если передано не как массив)
            if (!is_array($rule)) {
                $rule = [];
            }

            $srcKey = $rule['source'] ?? $ruleKey;
            $dstKey = $rule['target'] ?? $ruleKey;
            $processor = $rule['processor'] ?? null;

            // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: используем array_key_exists вместо !empty
            // !empty пропускает 0, '', false, null — это были скрытые баги
            $hasSourceKey = array_key_exists($srcKey, $source);

            if ($hasSourceKey) {
                $sourceValue = $source[$srcKey];

                if ($processor !== null) {
                    // Если processor — callable, вызываем его
                    // Если нет — используем как фиксированное значение
                    $result = is_callable($processor)
                        ? $processor($sourceValue, $source)
                        : $processor;
                } else {
                    $result = $sourceValue;
                }
            } else {
                // Ключ отсутствует в исходном массиве
                if (array_key_exists('default', $rule)) {
                    $default = $rule['default'];
                    $result = is_callable($default) ? $default($source) : $default;
                } else {
                    $result = $defaultUndefinedValue;
                }
            }

            // Приведение типа (если задано)
            if (isset($rule['type'])) {
                $result = self::castToType($result, $rule['type']);
            }

            $dataset[$dstKey] = $result;
        }

        return $dataset;
    }

    /**
     * Приводит значение к указанному типу.
     * Вынесен в отдельный метод для чистоты кода
     */
    public static function castToType(mixed $value, string $type): mixed
    {
        // match (PHP 8.0+) — быстрее и безопаснее switch
        return match (strtolower($type)) {
            'int', 'integer'    => (int) $value,
            'float', 'double'   => (float) $value,
            'bool', 'boolean'   => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'string', 'str'     => (string) $value,
            'array'             => (array) $value,
            default             => throw new InvalidArgumentException(
                sprintf('Unsupported type "%s" for explodeToType.', $type)
            ),
        };
    }

}