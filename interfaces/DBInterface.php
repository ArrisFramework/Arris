<?php

namespace Arris;

use Arris\DB\SimpleQueryBuilder;
use Monolog\Logger;

interface DBInterface {
    const MYSQL_ERROR_DUPLICATE_ENTRY = 1062;

    /**
     * Predicted (early) initialization
     *
     *
     * @param $suffix
     * @param $config
     * $config must have fields:
     * <code>
     *  'driver' (default mysql)
     *  'hostname' (default localhost)
     *  'database' (default mysql)
     *  'username' (default root)
     *  'password' (default empty)
     *  'port' (default 3306)
     *
     * optional:
     *  'charset'
     *  'charset_collate'
     * </code>
     * @param Logger|null $logger
     * @param $options - options [optional]: 'collect_time' => false|true, 'collect_query => true
     *
     * @throws \Exception
     */
    public static function init($suffix, $config, Logger $logger = null, array $options = []);

    public static function buildUpdateQuery(string $table, array $dataset = [], $where_condition = null):string;
    public static function buildReplaceQuery(string $table, array $dataset):string;
    public static function buildReplaceQueryMVA(string $table, array $dataset, array $mva_attributes):array;

    public static function makeQuery():SimpleQueryBuilder;

    public static function makeInsertQuery(string $table, &$dataset):string;
    public static function makeUpdateQuery(string $table, &$dataset, $where_condition):string;
    public static function makeReplaceQuery(string $table, array &$dataset, string $where = '');

    public static function getConfig($suffix = null);
    public static function setConfig(array $config, $suffix = null);
    public static function getLogger($suffix = null);
}