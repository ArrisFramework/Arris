<?php

function o($scheme = '')
{
    return $scheme ?: getenv('OPTION') ?: 'http';
}

var_dump( o() ); // http
var_dump( o('ftp') ); // ftp

putenv('OPTION=https');

var_dump( o() ); // https
var_dump( o('http')); // http
var_dump( o('ftp') ); // ftp
