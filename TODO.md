# CLI Tables

https://packagist.org/packages/jc21/clitable -- выглядит отлично, но таблицы нужно именно _создавать_

https://packagist.org/packages/miloske85/php-cli-table - ?

https://packagist.org/packages/sevenecks/tableify - 

https://packagist.org/packages/pgooch/php-ascii-tables

# DB

А так?

```
DB::suffix('suffix')->config($config)->logger($logger)->options([])->init();
```

# FileSearchIterator

**ИСПРАВЛЕНО (2026-07-13):** Вместо отдельного класса добавлен `FS::findFiles()`.
См. `sources/Helpers/FS.php` — статический метод с фильтрацией по расширению, началу/концу имени.

# Result

- как добавить кастинг `toArray()` у экземпляра Result? 

# OptionHelperClass

```php
$options = [];

Option::get($options)->key('xxx')->env('yyy')->_('zzz');

// что эквивалентно: setOption($options, 'xxx', 'yyy', 'zzz')

// опускаем вызов метода и получаем результат, эквивалетный опции null.

// или даже хелпер

Option($options)->key('xxx')->env('yyy')->_('zzz);

_('z') return 'z'

env('y') return getenv('y')

key('x') return если в массиве опций есть ключ x то его значение
 
if (getenv('y') !== false ) {
    return getenv('y')
} else {
    return $this; 
}

но это ничего не даст. Нужен дополнительный метод, который таки вернет результтт цепочки:

->get()

Как методу PHP определить, что он последний в цепочке вызовов и нужно вернуть значение?

Если только:

Option::_()->key()->env()->_()->from($options);

```
