# Hooks

Простейший механизм-обёртка над классом sabre\event ( http://sabre.io/event/ ) 

```
Hook::init();

// где-то там в плагинах на хуки вешаем методы
Hook::register('post:add:comment', function (){
    echo 'Event post:add:comment ', PHP_EOL;
});

Hook::register('post:add:topic', function (){
    echo 'Event post:add:topic', PHP_EOL;
});

Hook::register('example', function (){
    echo 100, PHP_EOL;
}, 100);

Hook::register('example', function (){
    echo 90, PHP_EOL;
}, 90);


// где-то там, в ядре делаем вызовы хуков
Hook::run('post:add:topic');
Hook::run('post:add:comment');
Hook::run('create');

Hook::run('example');

```

Как использовать в шаблонах Smarty:
```
{assign var=foo value=Arris\Hook::run('')}
```
https://www.smarty.net/docs/en/advanced.features.static.classes.tpl

или подключить класс: https://www.smarty.net/docs/en/api.register.class.tpl

```
<?php
namespace my\php\application {
  class Bar {
    $property = "hello world";
  }
}

$smarty = new Smarty();
$smarty->registerClass("Foo", "\my\php\application\Bar");
```
...
```
{* Foo translates to the real class \my\php\application\Bar *}
{Foo::$property}
```


-------------

## Register class for use within a template

```php
<?php

class Bar {
  $property = "hello world";
}

$smarty = new Smarty();
$smarty->registerClass("Foo", "Bar");
```

   
```php
{* Smarty will access this class as long as it's not prohibited by security *}
{Bar::$property}
{* Foo translates to the real class Bar *}
{Foo::$property}
```
   
## 14.37. Register namespaced class for use within a template

```php
<?php
namespace my\php\application {
  class Bar {
    $property = "hello world";
  }
}

$smarty = new Smarty();
$smarty->registerClass("Foo", "\my\php\application\Bar");

{* Foo translates to the real class \my\php\application\Bar *}
{Foo::$property}
```
   



