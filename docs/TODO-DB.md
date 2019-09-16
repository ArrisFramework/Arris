# DB next

Подумать начет общего рефакторинга методов

Например, getRowCount() возвращает строго count(*) без условий. И должно оно 
работать не статиком, а от коннекшена

DB::C()->getRowCount(table, [condition]);

# 2019-06-20

Вообще, переписать. Так, чтобы 

DB::C() - возвращал \PDO коннекшен

И работало искаропки:

DB::C()->query()

==========
Соответственно фасад:

DB($suffix = null), который === DB::getConnection($suffix)

DB()->query()

эквивалентно

DB::C()->query()

==============

Проблема сейчас: DB::C() возвращают \PDO, а не инстанс DB, из которого 
можно было бы вызвать динамические методы.

Поэтому надо при вызове динамических методов как-то передавать
идентификатор подключения.



Решение: https://www.php.net/manual/ru/language.oop5.overloading.php#object.call ?
```
public function __call($method, $arguments)
    {
        if (method_exists(DB::class, $method)) {
            return DB::{$method}($arguments);
        } elseif (method_exists(\PDO::class, $method)) {
            return DB::getDefaultConnection()->{$method}($arguments);    
        }
    }
```

Другой вариант: 

`DB()` возвращает инстанс класса с указанным id коннекшена.

В классе определены методы:

->pdo() = возвращает \PDO коннекшен, к которому можно применять обычные методы 

public function query()
{
	$this-> .... ?
	
	Посмотреть как сделано в Foolz/SphinxQL
}

?????

DB::C() return instance of this class (из массива инстансов)

---
```
/**
 * Build INSERT-query by dataset for given table
 *
 * @param $tablename
 * @param $dataset
 * @return string
 */
function makeInsertQuery($tablename, &$dataset):string
{
    $set = [];
    if (empty($dataset)) {
        return "INSERT INTO {$tablename} () VALUES (); ";
    }

    $query = "INSERT INTO `{$tablename}` SET ";

    foreach ($dataset as $index => $value) {

        if (strtoupper(trim($value)) === 'NOW()') {
            $set[] = "\r\n `{$index}` = NOW()";
            unset($dataset[ $index ]);
            continue;
        }

        $set[] = "\r\n `{$index}` = :{$index}";
    }

    $query .= implode(', ', $set) . ' ;';

    return $query;
}


```














