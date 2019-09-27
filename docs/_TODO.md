Рекомендуемый способ инициализации сторонних классов:

```
public static function init($options, $logger = null)
{
    // установка переменных из опций. 
    // это пример 

    self::$default_jpeg_quality = setOption($options, 'JPEG_COMPRESSION_QUALITY', 'STORAGE.JPEG_COMPRESSION_QUALITY', 100);

    // фильтр допустимых значений
    self::$default_jpeg_quality
        = is_integer(self::$default_jpeg_quality)
        ? min(self::$default_jpeg_quality, 100)
        : 100;

    // инициализация логгера

    self::$_logger 
        = $logger instanceof Logger
        ? $logger
        : AppLogger::addNullLogger();

    // тогда проверка if (self::$logger instance of Logger) не обязательна
}


```