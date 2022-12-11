<?php


namespace Arris\DB;

interface DBConnectionInterface
{
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

    public static function getLastInsertId($suffix = NULL):int;
}



