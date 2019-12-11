```
self::$_collect_metrics = array_key_exists('collect_time', $options) && $options['collect_time'];
```
Похоже, эквивалентно
```
setOption($options, 'collect_time')
```