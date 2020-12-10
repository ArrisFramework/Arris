<?php

require_once __DIR__ .  '/../../vendor/autoload.php';

use Arris\DB;

$x = DB::makeQuery()->insert('articles')->data(['x' => 1])->build();

var_dump($x);

$y = DB::makeQuery()->replace('articles')->data(['x' => 2, 'title' => 'yyyy'])->where(['x=1'])->build();

var_dump($y);

var_dump( DB::makeQuery()->select()->from('articles')->where([ 'x=1' ])->build()  );
