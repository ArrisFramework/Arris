<?php


namespace Arris;


interface DBInstanceInterface
{
    public function getRowCount($table, $suffix = NULL);
    public static function getInstance($suffix = NULL):\PDO;
}