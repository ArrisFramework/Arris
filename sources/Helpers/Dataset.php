<?php

namespace Arris\Helpers;

use InvalidArgumentException;

class Dataset implements DatasetInterface
{
    /**
     * Конвертирует в JSON
     *
     * @param mixed $data
     * @param int $flags
     *
     * @return string
     */
    public static function jsonize(mixed $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR):string
    {
        return json_encode($data, $flags);
    }

    /**
     * Генерирует новый массив на основе исходного по набору правил.
     *
     * Структура правила:
     * KEY - имя правила
     *  - source    - из какого поля исходного массива брать данные? Если отсутствует - совпадает с ключом
     *  - target    - в какое поле целевого массива записать данные? Если отсутствует - совпадает с ключом
     *  - default   - значение поля в целевом массиве, если в исходном оно не найдено. Если не задано, то null (переопределимо 3 аргументом).
     *  - processor - возвращаемый результат ИЛИ closure - функция, получающая два значения (исходное и исходный массив) и возвращающая значение
     *  - type      - приведение типа: 'int', 'float', 'bool', 'string', 'array'
     *
     *  Теперь о нюансах:
     *  1) processor:
     *  а) - НЕ Closure - в результирующий массив передается его значение (которому может быть задан тип)
     *  б) - Closure - передается результат функции
     *  2) чтобы просто скопировать ключ:значение из исходного массива, достаточно указать пустое правило
     *     для ключа ( например, `'mode' => []` )
     *  3) результирующий массив содержит ТЕ И ТОЛЬКО ТЕ ключи, которые указаны в списке ПРАВИЛ.
     *  4) если в исходном массиве нет ключа XXX, то в результирующем будет XXX => null (по умолчанию)
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
     *
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
        return match (strtolower($type)) {
            'int', 'integer'    => (int) $value,
            'float', 'double'   => (float) $value,
            'bool', 'boolean'   => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'string', 'str'     => (string) $value,
            'array'             => is_array($value) ? $value : [$value],
            default             => $value
        };
    }


}