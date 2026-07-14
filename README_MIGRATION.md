# getAllowedValue()

```php

// старый код
function getAllowedValue( $data, $allowed_values_array, $default_value = NULL )
{
    if (empty($data)) {
        return $default_value;
    } else {
        $key = array_search($data, $allowed_values_array);
        return ($key !== FALSE )
            ? $allowed_values_array[ $key ]
            : $default_value;
    }
}

```

### Миграция (ИСПРАВЛЕНО 2026-07-13)

**Прямая замена** `getAllowedValue($data, $allowed, $default)` → `Arrays::filterValueForAllowed($value, $allowed, $default)`:
```php
// Было:
$type_main = getAllowedValue($type, self::VALID_PLACE_TYPES, 'any');
$sort = getAllowedValue($_REQUEST['sort-type'], ['title', 'id', 'date_updated'], 'id');

// Стало (по значению):
$type_main = Arrays::filterValueForAllowed($type, self::VALID_PLACE_TYPES, 'any');
$sort = Arrays::filterValueForAllowed($_REQUEST['sort-type'], ['title', 'id', 'date_updated'], 'id');
```

**Альтернатива** (массив + ключ) → `Arrays::filterArrayForAllowed($array, $key, $allowed, $default)`:
```php
// Стало:
$sort = Arrays::filterArrayForAllowed($_REQUEST, 'sort-type', ['title', 'id', 'date_updated'], 'id');
```

Также доступен `Arrays::allowed()` — identical-контракт, `filterValueForAllowed` делегирует ему.

