## DBQueryBuilder

Не поддерживает конструкции вида:
```
$sql = (new DBQueryBuilder())
            ->select("COUNT(*)")
            ->from('articles_comments')
            ->where(['item' => ':article_id', 'deleted' => 0 ])
            ->build();
```
превращает их в:
```sql
SELECT  COUNT(*)  FROM articles_comments  WHERE :article_id AND 0
```

1. То есть интерпретирует аргументы where не более чем как массив строк, а не массив соотношений.

2. Не понимает два `->where($field, $value)`

3. Не понимает аргументы `->where($field, $value, $condition = '=', $logic_operator = 'AND' )`

4. `->build()` метод должен очищать все имеющиеся значения. 

5. Зачем всё это? Нужен лёгкий билдер запросов - и проверенный сообществом!

## NB

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