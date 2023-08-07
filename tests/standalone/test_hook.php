<?php

use Arris\Hook;

require_once __DIR__ . '/../../vendor/autoload.php';

Hook::init();

// где-то там в плагинах на хуки вешаем методы
Hook::register('post:add:comment', function (){
    echo 'Event post:add:comment ', PHP_EOL;
});

Hook::register('post:add:topic', function (){
    echo 'Event post:add:topic', PHP_EOL;
});

Hook::register('example', function (){
    echo "Example: 100", PHP_EOL;
}, 100);

Hook::register('example', function (){
    echo "Example: 90", PHP_EOL;
}, 90);


// где-то там, в ядре делаем вызовы хуков
// Hook::run('post:add:topic');
// Hook::run('post:add:comment');
// Hook::run('create');

Hook::run('example');