<?php

require_once __DIR__ .  '/../../vendor/autoload.php';

use Arris\DBMulti;

$x = DBMulti::makeQuery()->insert('articles')->data(['x' => 1])->build();

var_dump($x);

$y = DBMulti::makeQuery()->replace('articles')->data(['x' => 2, 'title' => 'yyyy'])->where(['x=1'])->build();

var_dump($y);

var_dump( DBMulti::makeQuery()->select()->from('articles')->where([ 'x=1' ])->build()  );
