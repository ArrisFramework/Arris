<?php

namespace Arris\Database;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class DBMultiConnector implements DBMultiConnectorInterface
{
    /**
     * \PDO instances
     * @var array
     */
    private static $_instances = [];

    /**
     * Connection configs
     * @var array
     */
    private static $_configs = [];

    /**
     * Connection Loggers
     * @var array
     */
    private static $_loggers = [];

    /**
     * DB constructor.
     * @param $suffix
     * @throws \RuntimeException
     */
    public function __construct($suffix = null)
    {
        $config_key = self::getKey($suffix);
        $config = self::getConfig($suffix);
        $logger = self::getLogger($suffix);

        $state_is_error = false;
        $state_error_code = 0;
        $state_error_msg = '';

        try {
            if (is_null($config)) {
                throw new RuntimeException("Arris\DBMultiConnector class can't find configuration data for suffix {$suffix}" . PHP_EOL, 2);
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

            self::$_instances[$config_key] = $dbh;

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

            self::$_configs[$config_key] = null;

            if ($logger instanceof LoggerInterface) {
                $logger->emergency("Arris\DBMultiConnector Runtime error: ", [$e->getMessage(), $e->getCode()]);
            }
        }

        if ($state_is_error === true) {
            throw new RuntimeException($state_error_msg, $state_error_code);
        }

        self::$_configs[$config_key] = $config;
    }

    /**
     *
     * @param $suffix
     * @param $config
     * @param LoggerInterface|null $logger
     * @param array $options
     * @throws RuntimeException
     */
    public static function init($suffix, $config, LoggerInterface $logger = null, array $options = [])
    {
        $config_key = self::getKey($suffix);

        if (!is_array($config) || empty($config)) {
            $message = __METHOD__
                . ' can\'t use given data: '
                . PHP_EOL . var_export($config, true) . PHP_EOL
                . ' as configuration for database connection with '
                . (is_null($suffix) ? 'default suffix ' : "suffix {$suffix}");

            if ($logger instanceof LoggerInterface) {
                $logger->emergency($message);
            }

            throw new RuntimeException($message);
        }

        self::$_loggers[$config_key]
            = $logger instanceof LoggerInterface
            ? $logger
            : new NullLogger();

        self::setConfig($config, $suffix);
        self::$_instances[$config_key] = (new self($suffix))->getInstance($suffix);
    }

    /**
     * Get connection config
     *
     * @param null $suffix
     * @return mixed|null
     */
    public static function getConfig($suffix = null)
    {
        $config_key = self::getKey($suffix);
        return self::$_configs[ $config_key ] ?? NULL;
    }

    /**
     * @param null $suffix
     * @return LoggerInterface
     */
    public static function getLogger($suffix = null)
    {
        $config_key = self::getKey($suffix);
        return self::$_loggers[ $config_key ] ?? null;
    }

    /**
     * Set connection config
     *
     * @param $config
     * @param null $suffix
     */
    public static function setConfig(array $config, $suffix = null)
    {
        $config_key = self::getKey($suffix);
        self::$_configs[$config_key] = $config;
    }

    public static function C($suffix = null): \PDO
    {
        return self::getConnection($suffix);
    }

    public static function getConnection($suffix = null): \PDO
    {
        return self::getInstance($suffix);
    }

    /**
     * Get class instance == connection instance
     *
     * @param null $suffix
     * @return \PDO
     * @throws RuntimeException
     */
    public static function getInstance($suffix = null): \PDO
    {
        $key = self::getKey($suffix);
        if (self::checkInstance($suffix)) {
            return self::$_instances[$key];
        }

        new self($suffix);
        return self::$_instances[$key];
    }

    /**
     * Check existance of connection in instances array
     *
     * @param null $suffix
     * @return bool
     */
    private static function checkInstance($suffix = null):bool
    {

        $key = self::getKey($suffix);
        return ( array_key_exists($key, self::$_instances) && self::$_instances[$key] !== null  );
    }

    /**
     * Converts connection suffix to internal connection key
     *
     * @param null $suffix
     * @return string
     */
    private static function getKey($suffix = null):string
    {
        return 'database' . ($suffix ? ":{$suffix}" : '');
    }

}