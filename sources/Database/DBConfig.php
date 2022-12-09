<?php

namespace Arris\Database;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DBConfig
{
    const DEFAULT_CHARSET = 'utf8';
    const DEFAULT_CHARSET_COLLATE = 'utf8_general_ci';

    /**
     * @var AbstractLogger
     */
    public $logger;

    /**
     * @var array
     */
    public $db_config;

    /**
     * @var string
     */
    public $driver;

    /**
     * @var string
     */
    public $hostname;

    /**
     * @var int
     */
    public $port;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $database;

    /**
     * @var bool
     */
    public $is_lazy;

    /**
     * @var string
     */
    public $charset;

    /**
     * @var string
     */
    public $charset_collate;

    /**
     * @var float
     */
    public $slow_query_threshold;

    /**
     * @var int
     */
    public $total_queries = 0;

    /**
     * @var float
     */
    public $total_time = 0;

    public function __construct(array $connection_config, array $options = [], LoggerInterface $logger = null)
    {
        $this->logger = is_null($logger) ? new NullLogger() : $logger;

        if (empty($connection_config)) {
            $this->logger->emergency("[DBWrapper Error] Connection config is empty");
            throw new \RuntimeException("[DBWrapper Error] Connection config is empty");
        }

        $this->db_config = $connection_config;
        $this->driver   = $this->db_config['driver'] ?? 'mysql';
        $this->hostname = $this->db_config['hostname'] ?? '127.0.0.1';
        $this->port     = $this->db_config['port'] ?? 3306;
        $this->username = $this->db_config['username'] ?? 'root';
        $this->password = $this->db_config['password'];
        $this->database = $this->db_config['database'];
        $this->is_lazy  = true;

        if (!array_key_exists('charset', $this->db_config)) {
            $this->charset = self::DEFAULT_CHARSET;
        } elseif (!is_null($this->db_config['charset'])) {
            $this->charset = $this->db_config['charset'];
        } else {
            $this->charset = null;
        }

        if (!array_key_exists('charset_collate', $this->db_config)) {
            $this->charset_collate = self::DEFAULT_CHARSET_COLLATE;
        } elseif (!is_null($this->db_config['charset_collate'])) {
            $this->charset_collate = $this->db_config['charset_collate'];
        } else {
            $this->charset_collate = null;
        }

        // ms
        $this->slow_query_threshold = (array_key_exists('slow_query_threshold', $options)) ? (float)$options['slow_query_threshold'] : 1000;

        // $this->is_lazy = array_key_exists('lazy', $options) ? (bool)$options['lazy'] : true;
        $this->is_lazy = !array_key_exists('lazy', $options) || (bool)$options['lazy']; // if default = false -> remove '!' from first part.

        // microseconds
        $this->slow_query_threshold /= 1000;
    }

    /**
     * @param $time
     * @param int $decimals
     * @param string $decimal_separator
     * @param string $thousands_separator
     * @return string
     */
    public function formatTime($time = 0, int $decimals = 6, string $decimal_separator = '.', string $thousands_separator = ''): string
    {
        return number_format($time, $decimals, $decimal_separator, $thousands_separator);
    }

}
