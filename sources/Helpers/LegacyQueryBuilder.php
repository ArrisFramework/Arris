<?php

namespace Arris\Helpers;

use InvalidArgumentException;

class LegacyQueryBuilder
{
    /**
     * Строит INSERT-запрос на основе массива данных для указанной таблицы.
     * Поддерживает специальную конструкцию 'NOW()' для полей с датой/временем.
     *
     * Примеры:
     *  - makeInsertQuery('users', ['name' => 'John', 'created_at' => 'NOW()'])
     *    => "INSERT INTO `users` SET `name` = :name, `created_at` = NOW();"
     *
     * @param string $table Имя таблицы (будет экранировано)
     * @param array<string, mixed> $dataset Массив данных (мутируется: NOW() удаляется)
     * @return string SQL-запрос с именованными плейсхолдерами
     * @throws InvalidArgumentException Если имя таблицы невалидно
     */
    public static function makeInsertQuery(string $table, array &$dataset): string
    {
        $safeTable = self::escapeIdentifier($table);

        if (empty($dataset)) {
            return "INSERT INTO {$safeTable} () VALUES ();";
        }

        $set = [];
        foreach ($dataset as $index => $value) {
            if (self::isNowFunction($value)) {
                $set[] = self::escapeIdentifier($index) . ' = NOW()';
                unset($dataset[$index]);
                continue;
            }
            $set[] = self::escapeIdentifier($index) . ' = :' . $index;
        }

        return "INSERT INTO {$safeTable} SET " . implode(', ', $set) . ';';
    }

    /**
     * Строит UPDATE-запрос на основе массива данных для указанной таблицы.
     *
     * Примеры:
     *  - makeUpdateQuery('users', ['status' => 'active'], ['id' => 5])
     *    => "UPDATE `users` SET `status` = :status WHERE `id` = 5;"
     *  - makeUpdateQuery('users', ['name' => 'John'], 'id = 5')
     *    => "UPDATE `users` SET `name` = :name WHERE id = 5;"
     *
     * @param string $table Имя таблицы (будет экранировано)
     * @param array<string, mixed> $dataset Массив данных для обновления (мутируется)
     * @param array<string, mixed>|string|null $whereCondition WHERE условие
     * @return string SQL-запрос с именованными плейсхолдерами
     * @throws InvalidArgumentException Если dataset пустой или имя таблицы невалидно
     */
    public static function makeUpdateQuery(string $table, array &$dataset, array|string|null $whereCondition = null): string
    {
        $safeTable = self::escapeIdentifier($table);

        if (empty($dataset)) {
            throw new InvalidArgumentException('Dataset cannot be empty for UPDATE query.');
        }

        $set = [];
        foreach ($dataset as $index => $value) {
            if (self::isNowFunction($value)) {
                $set[] = self::escapeIdentifier($index) . ' = NOW()';
                unset($dataset[$index]);
                continue;
            }
            $set[] = self::escapeIdentifier($index) . ' = :' . $index;
        }

        $query = "UPDATE {$safeTable} SET " . implode(', ', $set);

        $whereClause = self::buildWhereClause($whereCondition);
        if ($whereClause !== '') {
            $query .= ' ' . $whereClause;
        }

        return $query . ';';
    }

    /**
     * Строит REPLACE-запрос (MySQL-specific) на основе массива данных.
     *
     * @param string $table Имя таблицы (будет экранировано)
     * @param array<string, mixed> $dataset Массив данных (мутируется)
     * @param string $where Дополнительное WHERE условие (опционально)
     * @return string SQL-запрос с именованными плейсхолдерами
     * @throws InvalidArgumentException Если dataset пустой
     */
    public static function makeReplaceQuery(string $table, array &$dataset, string $where = ''): string
    {
        $safeTable = self::escapeIdentifier($table);

        if (empty($dataset)) {
            throw new InvalidArgumentException('Dataset cannot be empty for REPLACE query.');
        }

        $fields = [];
        foreach ($dataset as $index => $value) {
            if (self::isNowFunction($value)) {
                $fields[] = self::escapeIdentifier($index) . ' = NOW()';
                unset($dataset[$index]);
                continue;
            }
            $fields[] = self::escapeIdentifier($index) . ' = :' . $index;
        }

        $query = "REPLACE {$safeTable} SET " . implode(', ', $fields);

        if ($where !== '') {
            $query .= ' ' . $where;
        }

        return $query . ';';
    }

    /**
     * Строит REPLACE-запрос для Sphinx MVA (Multi-Value Attributes).
     * MVA-атрибуты вставляются как значения, а не как плейсхолдеры.
     *
     * Пример:
     *  - buildReplaceQueryMVA('products', ['id' => 1, 'tags' => [1,2,3]], ['tags'])
     *    => ["REPLACE INTO `products` (`id`, `tags`) VALUES (:id, (1,2,3));", ['id' => 1]]
     *
     * @param string $table Имя таблицы (будет экранировано)
     * @param array<string, mixed> $dataset Массив данных
     * @param array<string> $mvaAttributes Список имен MVA-атрибутов
     * @return array{0: string, 1: array<string, mixed>} [SQL-запрос, очищенный dataset]
     */
    public static function buildReplaceQueryMVA(string $table, array $dataset, array $mvaAttributes): array
    {
        $safeTable = self::escapeIdentifier($table);
        $datasetKeys = array_keys($dataset);

        if (empty($datasetKeys)) {
            throw new InvalidArgumentException('Dataset cannot be empty for REPLACE MVA query.');
        }

        // Экранируем имена колонок
        $escapedKeys = array_map([self::class, 'escapeIdentifier'], $datasetKeys);
        $columns = implode(', ', $escapedKeys);

        // Формируем VALUES с учетом MVA
        $values = array_map(
            function (string $key) use ($mvaAttributes, $dataset): string {
                if (in_array($key, $mvaAttributes, true)) {
                    // MVA-атрибут: вставляем как значение (1,2,3)
                    $value = $dataset[$key];
                    if (is_array($value)) {
                        return '(' . implode(',', array_map('intval', $value)) . ')';
                    }
                    return '(' . $value . ')';
                }
                // Обычное поле: плейсхолдер
                return ':' . $key;
            },
            $datasetKeys
        );

        $query = "REPLACE INTO {$safeTable} ({$columns}) VALUES (" . implode(', ', $values) . ');';

        // Удаляем MVA-атрибуты из dataset
        $newDataset = array_filter(
            $dataset,
            fn(string $key) => !in_array($key, $mvaAttributes, true),
            ARRAY_FILTER_USE_KEY
        );

        return [$query, $newDataset];
    }

    /**
     * Проверяет, является ли значение специальной функцией NOW().
     */
    private static function isNowFunction(mixed $value): bool
    {
        return is_string($value) && strtoupper(trim($value)) === 'NOW()';
    }

    /**
     * Экранирует идентификатор (имя таблицы или колонки) для защиты от SQL-инъекций.
     * Удаляет все символы кроме букв, цифр и подчеркивания.
     *
     * @param string $identifier Имя для экранирования
     * @return string Экранированное имя в backticks
     */
    private static function escapeIdentifier(string $identifier): string
    {
        // Удаляем все опасные символы, оставляем только безопасные
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
        return '`' . $safe . '`';
    }

    /**
     * Строит WHERE-условие из различных форматов ввода.
     *
     * @param array<string, mixed>|string|null $condition WHERE условие
     * @return string WHERE-клауза или пустая строка
     */
    private static function buildWhereClause(array|string|null $condition): string
    {
        if ($condition === null || $condition === '') {
            return '';
        }

        if (is_array($condition)) {
            // Массив: ['id' => 5, 'status' => 'active'] => "WHERE `id` = 5 AND `status` = 'active'"
            $parts = [];
            foreach ($condition as $key => $value) {
                $safeKey = self::escapeIdentifier($key);
                if ($value === null) {
                    $parts[] = "{$safeKey} IS NULL";
                } elseif (is_int($value) || is_float($value)) {
                    $parts[] = "{$safeKey} = {$value}";
                } else {
                    // Строковое значение: экранируем кавычки
                    $escapedValue = addslashes((string) $value);
                    $parts[] = "{$safeKey} = '{$escapedValue}'";
                }
            }
            return 'WHERE ' . implode(' AND ', $parts);
        }

        // Строка: добавляем WHERE если его нет
        if (stripos($condition, 'WHERE') === false) {
            return 'WHERE ' . $condition;
        }

        return $condition;
    }


}