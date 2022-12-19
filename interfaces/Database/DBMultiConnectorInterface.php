<?php

namespace Arris\Database;

use Psr\Log\LoggerInterface;
use RuntimeException;

interface DBMultiConnectorInterface
{
    const MYSQL_ERROR_DUPLICATE_ENTRY = 1062;

    /**
     * DB constructor.
     * @param $suffix
     * @throws \RuntimeException
     */
    public function __construct($suffix = null);

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
     * @param LoggerInterface|null $logger
     * @param $options - options [optional]: 'collect_time' => false|true, 'collect_query => true
     *
     * @throws \Exception
     */
    public static function init($suffix, $config, LoggerInterface $logger = null, array $options = []);

    public static function C($suffix = null): \PDO;

    public static function getConnection($suffix = null): \PDO;

    /**
     * Get class instance == connection instance
     *
     * @param null $suffix
     * @return \PDO
     * @throws RuntimeException
     */
    public static function getInstance($suffix = null): \PDO;





}