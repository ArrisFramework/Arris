# SizeFormatter

Замена Misc::size_format()

```php
// Базовое использование (SI units)
SizeFormatter::sizeFormat(1024);
// => '1 KB'

// Binary units (как в ОС)
SizeFormatter::sizeFormat(1024, binary: true);
// => '1 KiB'

// С десятичными знаками
SizeFormatter::sizeFormat(1536, decimals: 2);
// => '1.54 KB'

// Большие размеры
SizeFormatter::sizeFormat(1073741824); // 1 GB
// => '1 GB'

SizeFormatter::sizeFormat(1073741824, binary: true); // 1 GiB
// => '1 GiB'

// Отрицательные числа (например, изменение размера)
SizeFormatter::sizeFormat(-5242880);
// => '-5 MB'

// Кастомные разделители (для европейских локалей)
SizeFormatter::sizeFormat(1234567, 2, ',', '.');
// => '1,23 MB'

// Быстрая версия (для highload)
SizeFormatter::sizeFormatFast(1048576);
// => '1 MB'
```

Рекомендации:

- Для большинства случаев: используйте sizeFormat() — баланс скорости и гибкости
- Для highload: используйте sizeFormatFast() — максимальная производительность
- Для читаемости кода: используйте sizeFormatLoop() — понятная логика
- Для точности: всегда используйте binary: true (KiB, MiB) — это стандарт для файловых систем
