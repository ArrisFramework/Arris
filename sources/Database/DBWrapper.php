<?php

namespace Arris\Database;

use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method PDOStatement|false   _prepare($query = '', array $options = [])
 * @method int|false            exec(string $statement = '')
 * @method PDOStatement|false   _query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args)
 *
 * @method bool                 beginTransaction()
 * @method bool                 commit()
 * @method bool                 rollback()
 * @method bool                 inTransaction()
 *
 * @method mixed                getAttribute($attribute = '')
 * @method bool                 setAttribute($attribute, $value)
 *
 * @method string|false         lastInsertId($name = null)
 *
 * @method string               errorCode()
 * @method array                errorInfo()
 */
class DBWrapper
{
    const DEFAULT_CHARSET = 'utf8';
    const DEFAULT_CHARSET_COLLATE = 'utf8_general_ci';

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var array
     */
    private $db_config;

    /**
     * @var string
     */
    private $driver;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $charset;

    /**
     * @var string
     */
    private $charset_collate;

    /**
     * @var float|int
     */
    private $slow_query_threshold;

    public $is_lazy = true;

    /**
     * @var PDO
     */
    public $pdo;

    public $last_state = [
        'method'    =>  '',
        'query'     =>  '',
        'time'      =>  0,
        'comment'   =>  ''
    ];

    /**
     * @var DBConfig
     */
    private $config;

    public function __construct(array $connection_config, array $options = [], LoggerInterface $logger = null)
    {
        $this->config = new DBConfig($connection_config, $options, $logger);

        $this->logger = is_null($logger) ? new NullLogger() : $logger;

        if ($this->is_lazy === false) {
            $this->initConnection();
        }
    }

    /**
     * @todo: only MySQL supported now
     *
     * @return void
     */
    private function initConnection()
    {
        switch ($this->config->driver) {
            case 'mysql': {
                $dsl = sprintf("mysql:host=%s;port=%s;dbname=%s",
                    $this->config->hostname,
                    $this->config->port,
                    $this->config->database);

                $this->pdo = new PDO($dsl, $this->config->username, $this->config->password);

                break;
            }
            case 'pgsql': {
                $dsl = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
                    $this->config->hostname,
                    $this->config->port,
                    $this->config->database,
                    $this->config->username,
                    $this->config->password);

                $this->pdo = new \PDO($dsl);

                break;
            }
            case 'sqlite': {
                $dsl = sprintf("sqlite:%s", realpath($this->config->hostname));
                $this->pdo = new \PDO($dsl);

                break;
            }
            default: {
                throw new \RuntimeException('Unknown database driver : ' . $this->config->driver);

                break;
            }
        }

        if ($this->config->charset) {
            $sql_collate = "SET NAMES {$this->config->charset}";
            if ($this->config->charset_collate) {
                $sql_collate .= " COLLATE {$this->config->charset_collate}";
            }
            $this->pdo->exec($sql_collate);
        }

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    private function ensureConnection()
    {
        if (empty($this->pdo)) {
            $this->initConnection();
        }
    }

    public function __call($function, $args)
    {
        if (empty($this->pdo)) {
            $this->initConnection();
        }

        $this->last_state['method'] = $function;

        if (in_array(strtolower($function), [ 'prepare' ])) {
            $this->updateLastState($args);
        }

        // invoke the original method & calc time cost
        $before_call = microtime(true);
        $result = call_user_func_array([$this->pdo, $function], $args);
        $after_call = microtime(true);

        $this->config->total_time += $this->last_state['time'] = $after_call - $before_call;
        $this->config->total_queries++;

        if ($this->last_state['time'] >= $this->config->slow_query_threshold) {
            $this->logger->debug($function);
        }

        return $result;
    }

    public function query()
    {
        if (empty($this->pdo)) {
            $this->initConnection();
        }

        $args = func_get_args();

        $this->updateLastState($args);

        $result = call_user_func_array([$this->pdo, 'query'], $args);
        return new \Arris\Database\PDOStatement($result, $this->config);
    }

    public function prepare()
    {
        if (empty($this->pdo)) {
            $this->initConnection();
        }

        $args = func_get_args();

        $this->updateLastState($args);
        $result = call_user_func_array([$this->pdo, 'prepare'], $args);
        return new \Arris\Database\PDOStatement($result, $this->config);
    }

    public function getLastQueryTime(): string
    {
        return $this->config->formatTime($this->last_state['time']);
    }

    public function getLastState():array
    {
        $result = $this->last_state;
        $result['time'] = $this->config->formatTime($result['time']);
        return $result;
    }

    public function getStats()
    {
        return [
            'total_queries' =>  $this->config->total_queries,
            'total_time'    =>  $this->config->formatTime($this->config->total_time)
        ];
    }

    private function updateLastState($args)
    {
        $this->last_state['query'] = $args[0];
        if (preg_match('#^\/\*\s(.+)\s\*\/#', $args[0], $matches)) {
            $this->last_state['comment'] = $matches[0];
        };
    }
}

