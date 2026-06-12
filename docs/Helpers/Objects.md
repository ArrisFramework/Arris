# propertyExistsRecursive

Замена `Objects::property_exists_recursive()` с итеративным подходом и строгой типизацией

# propertyGetRecursive

Замена `Objects::property_get_recursive()`

```php
// Объект с вложенными объектами
$user = new stdClass();
$user->address = new stdClass();
$user->address->city = 'Moscow';

propertyExistsRecursive($user, 'address->city'); // true
propertyGetRecursive($user, 'address->city'); // 'Moscow'
propertyGetRecursive($user, 'address->zip', '->', 'Unknown'); // 'Unknown'

// Массив с вложенными массивами
$data = [
    'user' => [
        'profile' => [
            'settings' => ['theme' => 'dark']
        ]
    ]
];

propertyExistsRecursive($data, 'user->profile->settings'); // true
propertyGetRecursive($data, 'user->profile->settings->theme'); // 'dark'

// Смешанная структура (объект + массив)
$mixed = json_decode('{"user": {"posts": [{"id": 1}, {"id": 2}]}}');
propertyGetRecursive($mixed, 'user->posts->0->id'); // 1
```
