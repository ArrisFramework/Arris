<?php

namespace Arris\Database;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class DBSingleConnector
{
    private static bool $collect_metrics = false;

    /**
     * @var LoggerInterface|NullLogger
     */
    private static $logger;

    /**
     * @var array
     */
    private static array $config = [];

    /**
     * @var \PDO
     */
    private static $instance;

    /**
     *
     * @param $config
     * @param LoggerInterface|null $logger
     * @param array $options
     * @throws \RuntimeException
     */
    public static function init($config, LoggerInterface $logger = null, array $options = [])
    {
        self::$collect_metrics = array_key_exists('collect_time', $options) && $options['collect_time'];

        if (!is_array($config) || empty($config)) {
            $message = __METHOD__
                . ' can\'t use given data: '
                . PHP_EOL . var_export($config, true) . PHP_EOL
                . ' as configuration for database connection with ';

            if ($logger instanceof LoggerInterface) {
                $logger->emergency($message);
            }

            throw new \RuntimeException($message);
        }

        self::$logger
            = is_null($logger)
            ? new NullLogger()
            : $logger;

        self::$config = $config;
        self::$instance = (new self());
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    public static function C()
    {
        return self::$instance;
    }

    /**
     *
     */
    public function __construct()
    {
        $config = self::$config;
        $logger = self::$logger;

        $state_is_error = false;
        $state_error_code = 0;
        $state_error_msg = '';

        try {
            if (is_null($config)) {
                throw new RuntimeException("Arris\DBConnector class can't find configuration data" . PHP_EOL, 2);
            }

            $db_driver = $config['driver'] ?? 'mysql';
            $db_host = $config['hostname'] ?? 'localhost';
            $db_name = $config['database'] ?? 'mysql';
            $db_user = $config['username'] ?? 'root';
            $db_pass = $config['password'] ?? '';
            $db_port = $config['port'] ?? 3306;

            switch ($db_driver) {
                case 'mysql': {
                    $dsl = sprintf("mysql:host=%s;port=%s;dbname=%s",
                        $db_host,
                        $db_port,
                        $db_name);
                    $dbh = new \PDO($dsl, $db_user, $db_pass);

                    break;
                }
                case 'pgsql': {
                    $dsl = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
                        $db_host,
                        $db_port,
                        $db_name,
                        $db_user,
                        $db_pass);

                    $dbh = new \PDO($dsl);
                    break;
                }
                case 'sqlite': {
                    $dsl = sprintf("sqlite:%s", realpath($db_host));
                    $dbh = new \PDO($dsl);
                    break;
                }
                default: {
                    throw new RuntimeException('Unknown database driver : ' . $db_driver);
                    break;
                }
            } // switch

            if (isset($config['charset']) && !is_null($config['charset'])) {
                $sql = "SET NAMES {$config['charset']}";
                if (isset($config['charset_collate']) && !is_null($config['charset_collate'])) {
                    $sql .= " COLLATE {$config['charset_collate']}";
                }
                $dbh->exec($sql);
            }

            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            self::$instance = $dbh;

        } catch (\PDOException $e) {
            $state_is_error = true;

            $message = "Unable to connect `{$db_driver}:{$db_name}@{$db_host}:{$db_port}`, PDO CONNECTION ERROR: ";
            $state_error_msg = $message . $e->getMessage();
            $state_error_code = $e->getCode();

            if ($logger instanceof LoggerInterface) {
                $logger->emergency($message, [$e->getMessage(), $e->getCode()]);
            }

        } catch (RuntimeException $e) {
            $state_is_error = true;
            $state_error_msg = $e->getMessage();
            $state_error_code = $e->getCode();

            self::$config = [];

            if ($logger instanceof LoggerInterface) {
                $logger->emergency("Arris\DBConnector Runtime error: ", [$e->getMessage(), $e->getCode()]);
            }
        }

        if ($state_is_error === true) {
            throw new RuntimeException($state_error_msg, $state_error_code);
        }

        self::$config = $config;
    }


}