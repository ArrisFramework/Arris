<?php

/**
 * User: Karel Wintersky
 * Date: 01.03.2019, Version 2.3/Arris
 * Date: 09.06.2021, Version 2.4/Arris
 *
 * Library: https://github.com/KarelWintersky/Arris
 */

namespace Arris;

use Arris\DB\PDOWrapper\SimpleQueryBuilder;
use Exception;
use Monolog\Logger;
use PDOStatement;
use RuntimeException;

/**
 * Class DB
 */
class DBMulti implements DBInterface, DBConnectionInterface, DBInstanceInterface
{
    const VERSION = "2.4/Arris";

    private static $_current_connection = null;

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

    private static $_collect_metrics = false;

    public static $_db_requests_count = 0;

    public static $_db_requests_time = 0;

    /**
     * DB constructor.
     * @param $suffix
     * @throws Exception
     */
    public function __construct($suffix)
    {
        $config_key = self::getKey($suffix);
        $config = self::getConfig($suffix);
        $logger = self::getLogger($suffix);
        
        $state_is_error = false;
        $state_error_code = 0;
        $state_error_msg = '';

        try {
            if (is_null($config)) {
                throw new RuntimeException("Arris\DB class can't find configuration data for suffix {$suffix}" . PHP_EOL, 2);
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

            if ($logger instanceof Logger) {
                $logger->emergency($message, [$e->getMessage(), $e->getCode()]);
            }

        } catch (RuntimeException $e) {
            $state_is_error = true;
            $state_error_msg = $e->getMessage();
            $state_error_code = $e->getCode();

            self::$_configs[$config_key] = null;

            if ($logger instanceof Logger) {
                $logger->emergency("Arris\DB Runtime error: ", [$e->getMessage(), $e->getCode()]);
            }
        }

        if ($state_is_error === true) {
            throw new Exception($state_error_msg, $state_error_code);
        }

        self::$_configs[$config_key] = $config;
    }

    /**
     *
     * @param $suffix
     * @param $config
     * @param Logger|null $logger
     * @param array $options
     * @throws Exception
     */
    public static function init($suffix, $config, Logger $logger = null, array $options = [])
    {
        $config_key = self::getKey($suffix);

        self::$_collect_metrics = array_key_exists('collect_time', $options) && $options['collect_time'];

        if (!is_array($config) || empty($config)) {
            $message = __METHOD__
                . ' can\'t use given data: '
                . PHP_EOL . var_export($config, true) . PHP_EOL
                . ' as configuration for database connection with '
                . (is_null($suffix) ? 'default suffix ' : "suffix {$suffix}");

            if ($logger instanceof Logger) {
                $logger->emergency($message);
            }

            throw new Exception($message);
        }

        self::$_loggers[$config_key]
            = $logger instanceof Logger
            ? $logger
            : (new Logger('null'))->pushHandler(new \Monolog\Handler\NullHandler());

        self::setConfig($config, $suffix);
        self::$_instances[$config_key] = (new self($suffix))->getInstance($suffix);
    }

    /**
     * Отладочный метод, возвращает метрики использования
     *
     * @return array
     */
    public static function getMetrics()
    {
        return [
            'count' =>  self::$_db_requests_count,
            'time'  =>  self::$_db_requests_time
        ];
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
     * @return Logger|null
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

    public static function getConnection($suffix = null): \PDO
    {
        self::$_db_requests_count++;
        return self::getInstance($suffix);
    }

    public static function C($suffix = null): \PDO
    {
        return self::getConnection($suffix);
    }

    /**
     * Set current connection key for internal calls === setContext() ?
     *
     * @param $suffix
     */
    public static function setConnection($suffix)
    {
        self::$_current_connection = self::getKey($suffix);
    }

    /**
     * @param $suffix
     */
    public static function setDefaultConnection($suffix)
    {
        self::$_current_connection = self::getKey($suffix);
    }

    /**
     * @return |null
     */
    public static function getDefaultConnection()
    {
        return self::$_current_connection;
    }

    /**
     * Get class instance == connection instance
     *
     * @param null $suffix
     * @return \PDO
     * @throws Exception
     */
    public static function getInstance($suffix = NULL): \PDO
    {
        $key = self::getKey($suffix);
        if (self::checkInstance($suffix)) {
            return self::$_instances[$key];
        }

        new self($suffix);
        return self::$_instances[$key];
    }

    /**
     * Выполняет Query-запрос
     *
     * @param $query
     * @param null $suffix
     * @return false|PDOStatement
     * @throws Exception
     */
    public static function query($query, $suffix = NULL)
    {
        return self::getConnection($suffix)->query($query);
    }

    /**
     * Удаляет единичную строку
     *
     * @param string $table
     * @param string $field
     * @param $id
     * @return int
     * @throws Exception
     */
    public static function queryDeleteRow(string $table, string $field, $id): int
    {
        if (empty($table) || empty($field) || empty($id)) return false;

        $state = self::getConnection()->prepare("DELETE FROM {$table} WHERE {$field} = :id");
        return $state->execute(['id' => $id]);
    }

    /**
     * Get count(*) for given table
     *
     * @param $table
     * @param null $suffix
     * @return int
     * @throws Exception
     */
    public function getRowCount($table, $suffix = null)
    {
        if ($table == '') {
            return null;
        }

        $sth = self::getConnection($suffix)->query("SELECT COUNT(*) AS cnt FROM {$table}");

        return ($sth) ? $sth->fetchColumn() : null;
    }

    /**
     * Возвращает инстанс враппера для указанного соединения и устанавливает текущее соединение.
     *
     * @param null $suffix
     * @return mixed
     */
    public static function I($suffix = null)
    {
        $key = self::getKey($suffix);
        self::$_current_connection = $key;
        return self::$_instances[ $key ];
    }

    /**
     * get Last Insert ID
     *
     * @param null $suffix
     * @return int
     * @throws Exception
     */
    public static function getLastInsertId($suffix = null):int
    {
        return self::getConnection($suffix)->lastInsertId();
    }



    /**
     * Строит INSERT-запрос на основе массива данных для указанной таблицы.
     * В массиве допустима конструкция 'key' => 'NOW()'
     * В этом случае она будет добавлена в запрос и удалена из набора данных (он пере).
     *
     * @param $table    -- таблица
     * @param $dataset      -- передается по ссылке, мутабелен
     * @return string       -- результирующая строка запроса
     */
    public static function makeInsertQuery(string $table, &$dataset):string
    {
        if (empty($dataset)) {
            return "INSERT INTO {$table} () VALUES (); ";
        }

        $set = [];

        $query = "INSERT INTO `{$table}` SET ";

        foreach ($dataset as $index => $value) {
            if (strtoupper(trim($value)) === 'NOW()') {
                $set[] = "\r\n `{$index}` = NOW()";
                unset($dataset[ $index ]);
                continue;
            }

            $set[] = "\r\n `{$index}` = :{$index}";
        }

        $query .= implode(', ', $set) . ' ;';

        return $query;
    }

    /**
     * Build UPDATE query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @param $where_condition
     * @return bool|string
     */
    public static function makeUpdateQuery(string $table, &$dataset, $where_condition):string
    {
        $crlf = ''; // '\r\n';
        $set = [];

        if (empty($dataset)) {
            return false;
        }

        $query = "UPDATE `{$table}` SET";

        foreach ($dataset as $index => $value) {
            if (strtoupper(trim($value)) === 'NOW()') {
                $set[] = "{$crlf} `{$index}` = NOW()";
                unset($dataset[ $index ]);
                continue;
            }

            $set[] = "{$crlf}`{$index}` = :{$index}";
        }

        $query .= implode(', ', $set);

        if (is_array($where_condition)) {
            $where_condition = key($where_condition) . ' = ' . current($where_condition);
        }
        if ( is_string($where_condition ) && (false == strpos($where_condition, 'WHERE')) ) {
            $where_condition = " WHERE {$where_condition}";
        }
        if (is_null($where_condition)) {
            $where_condition = '';
        }

        $query .= " {$crlf} $where_condition ;";

        return $query;
    }

    public static function makeReplaceQuery(string $table, array &$dataset, string $where = '')
    {
        $fields = [];

        if (empty($dataset))
            return false;

        $query = "REPLACE `{$table}` SET ";

        foreach ($dataset as $index => $value) {
            if (strtoupper(trim($value)) === 'NOW()') {
                $fields[] = "`{$index}` = NOW()";
                unset($dataset[ $index ]);
                continue;
            }

            $fields[] = " `{$index}` = :{$index} ";
        }

        $query .= implode(', ', $fields);

        $query .= " \r\n" . $where . " ;";

        return $query;
    }

    public static function makeQuery():SimpleQueryBuilder
    {
        return (new SimpleQueryBuilder());
    }

    /**
     * Converts connection suffix to internal connection key
     *
     * @param null $suffix
     * @return string
     */
    private static function getKey($suffix = NULL):string
    {
        return 'database' . ($suffix ? ":{$suffix}" : '');
    }

    /**
     * Check existance of connection in instances array
     *
     * @param null $suffix
     * @return bool
     */
    private static function checkInstance($suffix = NULL):bool
    {

        $key = self::getKey($suffix);
        return ( array_key_exists($key, self::$_instances) && self::$_instances[$key] !== NULL  );
    }

    /* ================================================================================================================= */


    /**
     * @param string $table
     * @param array $dataset
     * @return string
     */
    public static function buildReplaceQuery(string $table, array $dataset):string
    {
        $dataset_keys = array_keys($dataset);

        $query = "REPLACE INTO `{$table}` (";

        $query.= implode(', ', array_map(function ($i){
            return "`{$i}`";
        }, $dataset_keys));

        $query.= " ) VALUES ( ";

        $query.= implode(', ', array_map(function ($i){
            return ":{$i}";
        }, $dataset_keys));

        $query.= " ) ";

        return $query;
    }

    /**
     * @param string $table
     * @param array $dataset
     * @param null $where_condition - строка условия без WHERE ('x=0 AND y=0' ) или массив условий ['x=0', 'y=0']
     * @return string
     */
    public static function buildUpdateQuery(string $table, array $dataset = [], $where_condition = null):string
    {
        $query = "UPDATE `{$table}` SET ";

        $query.= implode(', ', array_map(function ($key, $value){
            return "\r\n`{$key}` = :{$key}";
        }, array_keys($dataset), $dataset));

        $where
            = !empty($where_condition)
            ? "WHERE " . $where_condition
            : "";

        $query .= "\r\n {$where} ;";

        return $query;
    }

    /**
     * Применять как:
     *
     * list($update_query, $newdataset) = BuildReplaceQueryMVA($table, $original_dataset, $mva_attributes_list);
     * $update_statement = $sphinx->prepare($update_query);
     * $update_statement->execute($newdataset);
     *
     *
     * @param string $table             -- имя таблицы
     * @param array $dataset            -- сет данных.
     * @param array $mva_attributes     -- массив с именами ключей MVA-атрибутов (они вставятся как значения, а не как placeholder-ы)
     * @return array                    -- возвращает массив с двумя значениями. Первый ключ - запрос, сет данных, очищенный от MVA-атрибутов.
     */
    public static function buildReplaceQueryMVA(string $table, array $dataset, array $mva_attributes):array
    {
        $query = "REPLACE INTO `{$table}` (";

        $dataset_keys = array_keys($dataset);

        $query .= implode(', ', array_map( static function ($i){
            return "`{$i}`";
        }, $dataset_keys));

        $query .= " ) VALUES ( ";

        $query .= implode(', ', array_map(static function ($i) use ($mva_attributes, $dataset){
            return in_array($i, $mva_attributes) ? "({$dataset[$i]})" : ":{$i}";
        }, $dataset_keys));

        $query .= " ) ";

        $new_dataset = array_filter($dataset, static function ($value, $key) use ($mva_attributes) {
            return !in_array($key, $mva_attributes);
        }, ARRAY_FILTER_USE_BOTH);

        return [
            $query, $new_dataset
        ];
    }

}

/**
 * DB::C() helper
 *
 * @param null $suffix
 * @return \PDO
 * @throws Exception
 */
function DBC($suffix = null)
{
    return DBMulti::C($suffix);
}

function DBI($suffix = null)
{
    return DBMulti::I($suffix);
}

# -eof-
