<?php

declare(strict_types=1);

namespace Arris\Util;

class Request implements RequestInterface
{
    /**
     * Получает строковое значение из REQUEST с фильтрацией и валидацией
     *
     * @param string $field Имя поля
     * @param int $maxLength Максимальная длина (0 = без ограничения)
     * @param string $default Значение по умолчанию
     * @param bool $trim Обрезать пробелы
     * @param bool $allowEmpty Разрешить пустые значения
     * @param array|null $from
     * @return string
     */
    public static function str(
        string $field,
        int $maxLength = 0,
        string $default = '',
        bool $trim = true,
        bool $allowEmpty = true,
        ?array $from = null
    ): string {
        return self::string($field, $maxLength, $default, $trim, $allowEmpty, $from);
    }

    /**
     * Получает строковое значение из REQUEST с фильтрацией и валидацией
     *
     * @param string $field Имя поля
     * @param int $maxLength Максимальная длина (0 = без ограничения)
     * @param string $default Значение по умолчанию
     * @param bool $trim Обрезать пробелы
     * @param bool $allowEmpty Разрешить пустые значения
     * @param array|null $from
     * @return string
     */
    public static function string(
        string $field,
        int $maxLength = 0,
        string $default = '',
        bool $trim = true,
        bool $allowEmpty = true,
        ?array $from = null
    ): string {

        if (is_null($from)) {
            $from = $_REQUEST;
        }

        $value = $from[$field] ?? $default;
        $value = (string)$value;

        if ($trim) {
            $value = trim($value);
        }

        if (!$allowEmpty && $value === '') {
            return $default;
        }

        if ($maxLength > 0) {
            $value = mb_substr($value, 0, $maxLength, 'UTF-8');
        }

        return $value;
    }

    /**
     * Получает email с валидацией
     *
     * @param string $field Имя поля
     * @param string $default Значение по умолчанию
     * @return string
     */
    public static function email(string $field, string $default = '', ?array $from = null): string
    {
        $email = self::string($field, 254, $default, from: $from);

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return $default;
    }

    /**
     * Получает целое число с валидацией
     *
     * @param string $field Имя поля
     * @param int|null $min Минимальное значение
     * @param int|null $max Максимальное значение
     * @param int $default Значение по умолчанию
     * @param array|null $from
     * @return int
     */
    public static function int(
        string $field,
        ?int $min = null,
        ?int $max = null,
        int $default = 0,
        ?array $from = null
    ): int {

        if (is_null($from)) {
            $from = $_REQUEST;
        }

        $value = $from[$field] ?? $default;

        $value = filter_var($value, FILTER_VALIDATE_INT);

        if ($value === false) {
            return $default;
        }

        if ($min !== null && $value < $min) {
            return $default;
        }

        if ($max !== null && $value > $max) {
            return $default;
        }

        return $value;
    }

    /**
     * Получает булево значение
     *
     * @param string $field Имя поля
     * @param bool $default Значение по умолчанию
     * @param array|null $from
     * @return bool
     */
    public static function bool(string $field, bool $default = false, ?array $from = null): bool
    {
        if (is_null($from)) {
            $from = $_REQUEST;
        }

        $value = $from[$field] ?? $default;

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower($value);
            if ($value === 'true' || $value === '1' || $value === 'on') {
                return true;
            }
            if ($value === 'false' || $value === '0' || $value === 'off' || $value === '') {
                return false;
            }
        }

        if (is_numeric($value)) {
            return (int)$value !== 0;
        }

        return $default;
    }

    /**
     * Получает значение чекбокса как булево
     *
     * @param string $field Имя поля
     * @param bool $default Значение по умолчанию
     * @param array|null $from
     * @return bool
     */
    public static function checkbox(string $field, bool $default = false, ?array $from = null): bool
    {
        return self::bool($field, $default, $from);
    }

    /**
     * Получает массив значений (для множественного выбора)
     * НЕ поддерживает множественные массивы вида nominations[ids][]
     *
     * @param string $field Имя поля
     * @param array $default Значение по умолчанию
     * @param int $maxLength Максимальная длина каждого элемента
     * @return array
     */
    public static function array(
        string $field,
        array $default = [],
        int $maxLength = 0,
        ?array $from = null
    ): array {
        if (is_null($from)) {
            $from = $_REQUEST;
        }

        $value = $from[$field] ?? $default;

        if (!is_array($value)) {
            return $default;
        }

        $result = [];
        foreach ($value as $item) {
            $item = (string)$item;
            if ($maxLength > 0) {
                $item = mb_substr($item, 0, $maxLength, 'UTF-8');
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Получает массив значений (для множественного выбора)
     * Поддерживает вложенные массивы вида `nominations[ids][]`
     *
     * @param string $field Имя поля
     * @param array $default Значение по умолчанию
     * @param int $maxLength Максимальная длина каждого элемента
     * @param bool $transposeMatrix
     * @return array
     */
    public static function arr(
        string $field,
        array $default = [],
        int $maxLength = 0,
        bool $transposeMatrix = false,
        ?array $from = null
    ): array {
        if (is_null($from)) {
            $from = $_REQUEST;
        }

        $value = $from[$field] ?? $default;

        if (!is_array($value)) {
            return $default;
        }

        $processArray = function ($array) use (&$processArray, $maxLength) {
            $result = [];

            foreach ($array as $key => $item) {
                if (is_array($item)) {
                    $result[$key] = $processArray($item);
                } else {
                    $item = (string)$item;
                    if ($maxLength > 0) {
                        $item = mb_substr($item, 0, $maxLength, 'UTF-8');
                    }
                    $result[$key] = $item;
                }
            }

            return $result;
        };

        $arr = $processArray($value);

        if ($transposeMatrix) {
            $arr = self::transposeMatrix($arr);
        }

        return $arr;
    }

    /**
     * Получает URL с валидацией
     *
     * @param string $field Имя поля
     * @param string $default Значение по умолчанию
     * @return string
     */
    public static function url(string $field, string $default = '', ?array $from = null): string
    {
        $url = self::string($field, 2048, $default, from: $from);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return $default;
    }

    /**
     * Очищает строку от потенциально опасных символов
     *
     * @param string $field Имя поля
     * @param bool $allowHtml Разрешить HTML теги
     * @param array|null $from Источник данных
     * @param bool $noEmptyContent Удалять пустой контент
     * @return string
     */
    public static function text(string $field, bool $allowHtml = false, ?array $from = null, bool $noEmptyContent = true): string
    {
        $value = self::string($field, from: $from);

        if (!$allowHtml) {
            $value = strip_tags($value);
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if ($noEmptyContent) {
            do {
                $oldValue = $value;

                $value = preg_replace('/<br\s*\/?>/i', '', $value);
                $value = preg_replace('/<(div|p)[^>]*>\s*(?:<br\s*\/?>\s*)*\s*<\/\1>/i', '', $value);
                $value = preg_replace('/<p[^>]*>\s*(?:&nbsp;\s*)+\s*<\/p>/i', '', $value);
                $value = preg_replace('/<p[^>]*>[\s\x{00A0}]*<\/p>/iu', '', $value);

            } while ($oldValue !== $value);

            $value = preg_replace('/\s+/', ' ', $value);
            $value = trim($value);

            if (empty($value)) {
                return '';
            }
        }

        return $value;
    }

    /**
     * Преобразует структуру данных из формата "поля" в формат "строки"
     * (транспонирует матрицу)
     *
     * Преобразует:
     * ["ids" => [a, b, c], "titles" => [x, y, z]]
     * В:
     * [
     *   ["ids" => a, "titles" => x],
     *   ["ids" => b, "titles" => y],
     *   ["ids" => c, "titles" => z]
     * ]
     *
     * @param array $data Исходный массив
     * @return array Преобразованный массив
     */
    public static function transposeMatrix(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $count = count(reset($data));
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $row = [];
            foreach ($data as $fieldName => $fieldValues) {
                $row[$fieldName] = $fieldValues[$i] ?? null;
            }
            $result[] = $row;
        }

        return $result;
    }
}
