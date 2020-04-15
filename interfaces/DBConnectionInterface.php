<?php


namespace Arris;

use Arris\System\DBQueryBuilder;
use Monolog\Logger;

/**
 * Interface DBConnectionInterface
 */
interface DBConnectionInterface
{
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

    /**
     * Get PDO connection
     *
     * @param null $suffix
     * @return \PDO
     * @throws \Exception
     */
    public static function getConnection($suffix = NULL): \PDO;

    /**
     * Alias for get PDO connection
     *
     * @param null $suffix
     * @return \PDO
     * @throws \Exception
     */
    public static function C($suffix = NULL): \PDO;

    public static function query($query, $suffix = NULL);
    public static function queryDeleteRow(string $table, string $field, $id):int;

    public static function buildUpdateQuery(string $table, array $dataset = [], $where_condition = null):string;
    public static function buildReplaceQuery(string $table, array $dataset):string;
    public static function buildReplaceQueryMVA(string $table, array $dataset, array $mva_attributes):array;

    public static function makeQuery():DBQueryBuilder;

    public static function makeInsertQuery(string $table, &$dataset):string;
    public static function makeUpdateQuery(string $table, &$dataset, $where_condition):string;
    public static function makeReplaceQuery(string $table, array &$dataset, string $where = '');

    public static function getRowCount($table, $suffix = NULL):int;

    public static function getTablePrefix($suffix = NULL):string;
    public static function getInstance($suffix = NULL):\PDO;

    public static function getLastInsertId($suffix = NULL):int;

    public static function getConfig($suffix = NULL): array;
    public static function setConfig(array $config, $suffix = NULL);
    public static function getLogger($suffix = NULL);
}