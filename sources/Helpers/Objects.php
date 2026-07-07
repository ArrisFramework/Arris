<?php

namespace Arris\Helpers;

class Objects implements ObjectsInterface
{
    /**
     * https://www.php.net/manual/en/reflectionfunctionabstract.isclosure.php
     *
     * @throws \ReflectionException
     */
    public static function is_closure($suspected_closure): bool
    {
        $reflection = new \ReflectionFunction($suspected_closure);

        return $reflection->isClosure();
    }

    /**
     * Рекурсивно проверяет существование свойства у объекта или ключа у массива.
     *
     * https://gist.github.com/nyamsprod/10adbef7926dbc449e01eaa58ead5feb
     *
     * Примеры:
     *  - propertyExistsRecursive($user, 'address->city')           => true/false
     *  - propertyExistsRecursive($data, 'user->profile->settings') => true/false
     *  - propertyExistsRecursive($array, 'key1->key2->key3', '->') => true/false
     *
     * @param mixed $object Объект или массив для проверки
     * @param string $path Путь к свойству (например, 'address->city')
     * @param string $separator Разделитель в пути
     * @return bool true если свойство/ключ существует на всех уровнях
     */
    public static function propertyExistsRecursive(mixed $object, string $path, string $separator = '->'): bool
    {
        $properties = explode($separator, $path);

        foreach ($properties as $property) {
            if (is_object($object)) {
                if (!property_exists($object, $property)) {
                    return false;
                }
                $object = $object->{$property};
            } elseif (is_array($object)) {
                if (!array_key_exists($property, $object)) {
                    return false;
                }
                $object = $object[$property];
            } else {
                // Достигли скалярного значения, но путь не закончился
                return false;
            }
        }

        return true;
    }

    /**
     * Получает значение свойства у объекта или ключа у массива по вложенному пути.
     *
     * Примеры:
     *  - propertyGetRecursive($user, 'address->city', '->', 'Unknown')
     *  - propertyGetRecursive($data, 'user->profile->avatar', '->', null)
     *
     * @param mixed $object Объект или массив для доступа
     * @param string $path Путь к свойству (например, 'address->city')
     * @param string $separator Разделитель в пути
     * @param mixed $default Значение по умолчанию, если путь не найден
     * @return mixed Значение свойства или $default
     */
    public static function propertyGetRecursive(
        mixed $object,
        string $path,
        string $separator = '->',
        mixed $default = null
    ): mixed {
        $properties = explode($separator, $path);

        foreach ($properties as $property) {
            if (is_object($object)) {
                if (!property_exists($object, $property)) {
                    return $default;
                }
                $object = $object->{$property};
            } elseif (is_array($object)) {
                if (!array_key_exists($property, $object)) {
                    return $default;
                }
                $object = $object[$property];
            } else {
                return $default;
            }
        }

        return $object;
    }

}