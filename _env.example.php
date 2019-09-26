<?php

$_cdn_now_env = [
    'username'      =>  '',
    'password'      =>  '',
    'project_name'  =>  '',
    'project_token' =>  '',

    'client_token'  =>  ''
];

$_db = [
    'hostname'  =>  '',
    'database'  =>  '',
    'username'  =>  '',
    'password'  =>  '',
    'port'      =>  3306,
    'charset'   =>  '',                         // will default utf8
    'charset_collate' =>  '',        // default utf8_general_ci
];

return [
    'CDNNOW'    =>  $_cdn_now_env,
    'DATABASE'  =>  $_db
];
