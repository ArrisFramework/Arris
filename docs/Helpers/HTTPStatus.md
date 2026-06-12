# HTTPStatus 

ENUM для HTML-статусов


1. Базовое использование

```php
// Получение кода
$code = HttpStatus::OK->value; // 200

// Получение строки статуса
$line = HttpStatus::NOT_FOUND->getStatusLine(); // "HTTP/1.1 404 Not Found"

// Отправка заголовка
header(HttpStatus::MOVED_PERMANENTLY->getStatusLine());
```

2. Создание из числа

```php
// Из числа (выбросит ValueError, если код невалиден)
$status = HttpStatus::from(404); // HttpStatus::NOT_FOUND

// Безопасное создание (вернет null, если код невалиден)
$status = HttpStatus::tryFrom(999); // null
```

3. Проверка категории

```php
$status = HttpStatus::INTERNAL_SERVER_ERROR;

$status->isInformational(); // false
$status->isSuccess();       // false
$status->isRedirection();   // false
$status->isClientError();   // false
$status->isServerError();   // true
$status->isError();         // true
```

4. Использование в функциях

```php
public function sendResponse(HttpStatus $status, string $body = ''): void
{
    header($status->getStatusLine());

    if ($status->isRedirection()) {
        // Логика редиректа
    }
    
    if ($status->isError()) {
        // Логирование ошибки
        error_log("HTTP Error: {$status->getReasonPhrase()}");
    }
    
    echo $body;
}

// Использование
sendResponse(HttpStatus::OK, 'Success');
sendResponse(HttpStatus::NOT_FOUND, 'Page not found');
```

5. Switch с Enum (PHP 8.0+)

```php
match ($status) {
    HttpStatus::OK, HttpStatus::CREATED => 'Success',
    HttpStatus::NOT_FOUND => 'Page not found',
    HttpStatus::INTERNAL_SERVER_ERROR => 'Server error',
    default => 'Unknown status',
};
```


