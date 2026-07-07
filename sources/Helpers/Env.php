<?php
declare(strict_types=1);

namespace Arris\Helpers;

class Env implements EnvInterface
{
    /**
     * Читает переменную окружения с приведением типа.
     * Замена _env().
     */
    public static function get(string $key, mixed $default = null, string $type = ''): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        if ($type === '') {
            return $value;
        }

        return match ($type) {
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int'    => (int) $value,
            'float'  => (float) $value,
            'array'  => self::parseArrayEnv($value),
            'string' => (string) $value,
            default  => $default,
        };
    }

    /**
     * use function ArrisFunctions\setOption as setOption;
     *
     * ($options, $key, $env_key, $default) =>  $options[ $key ]
     * ([], $key, $env_key, $default)       =>  get_env( $env_key )
     * ($arr, null, $env_key, $default)     =>  get_env( $env_key )
     * ([], null, null, $default)           =>  default
     * ([], null, null, null)               =>  null
     *
     * @param array $options
     * @param string|null $key
     * @param string|null $envKey
     * @param string $default
     *
     * @return string
     */
    public static function option(
        array  $options,
        ?string $key,
        ?string $envKey = null,
        string $default = ''
    ): string {
        // 1. Приоритет у явно переданного значения
        if ($key !== null && isset($options[$key])) {
            return (string) $options[$key];
        }

        // 2. Fallback на переменную окружения
        if ($envKey !== null) {
            $envValue = getenv($envKey);
            if ($envValue !== false) {
                return $envValue;
            }
        }

        return $default;
    }

    /**
     * Парсит массив из env-переменной.
     * Поддерживает форматы: "a b c", "[a,b,c]", "a,b,c"
     */
    private static function parseArrayEnv(string $value): array
    {
        $cleaned = trim(str_replace(['[', ']'], '', $value));

        // Поддержка и запятых, и пробелов как разделителей
        if (str_contains($cleaned, ',')) {
            return array_map('trim', explode(',', $cleaned));
        }

        return array_values(array_filter(explode(' ', $cleaned)));
    }
}