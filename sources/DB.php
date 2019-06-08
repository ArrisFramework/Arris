<?php /** @noinspection ALL */

/**
 * User: Karel Wintersky
 * Date: 26.08.2018, time: 14:25 Version 1.5/LIBDb
 * Date: 20.09.2018, time: 16:34 Version 2.0/ArrisFramework
 * Date: 09.10.2018, time: 06:34 Version 2.1/ArrisFramework
 * Date: 01.03.2019, Version 2.3/Arris
 *
 * Library: https://github.com/KarelWintersky/Arris
 */

namespace Arris;

/**
 * Interface DBConnectionInterface
 */
interface DBConnectionInterface
{
    public static function init($suffix, $config);

    public static function getConnection($suffix = NULL): \PDO;
    public static function C($suffix = NULL): \PDO;

    public static function query($query, $suffix = NULL);

    public static function buildUpdateQuery(string $table, array $dataset = [], $where_condition = null):string;
    public static function buildReplaceQuery(string $table, array $dataset):string;
    public static function buildReplaceQueryMVA(string $table, array $dataset, array $mva_attributes):array;

    public static function makeInsertQuery($tablename, $dataset):string;
    public static function makeUpdateQuery($tablename, $dataset, $where_condition = ''):string;

    public static function getRowCount($table, $suffix = NULL):int;
    public static function getRowCountConditional($table, $field = '*', $condition = '', $suffix = NULL):int;

    public static function getTablePrefix($suffix = NULL):string;
    public static function getInstance($suffix = NULL):\PDO;

    public static function getLastInsertId($suffix = NULL):int;

    public static function checkTableExists($table = '', $suffix = NULL):bool;

    public static function getConfig($suffix = NULL): array;
    public static function setConfig(array $config, $suffix = NULL);
}

/**
 * Class DB
 */
class DB implements DBConnectionInterface
{
    const VERSION = "1.13";

    private static $_current_connection = null;

    /**
     * \PDO instances
     * @var array
     */
    private static $_instances = [];

    /**
     * DB Configs
     * @var array
     */
    private static $_configs = [];

    /**
     *
     * DB constructor.
     * @param $suffix
     */
    public function __construct($suffix)
    {
        $config_key = self::getKey($suffix);

        $config = self::getConfig($suffix);

        $dbhost = $config['hostname'] ?? 'localhost';
        $dbname = $config['database'] ?? 'mysql';
        $dbuser = $config['username'] ?? 'root';
        $dbpass = $config['password'] ?? '';
        $dbport = $config['port'] ?? 3306;

        $dsl = "mysql:host={$dbhost};port={$dbport};dbname={$dbname}";
        try {
            if ($config === NULL)
                throw new \Exception("DB class can't find configuration data for suffix {$suffix}" . PHP_EOL, 2);

            $dbh = new \PDO($dsl, $dbuser, $dbpass);

            if (isset($config['charset']) && isset($config['charset_collate'])) {
                $dbh->exec("SET NAMES {$config['charset']} COLLATE {$config['charset_collate']}");
            }

            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            self::$_instances[$config_key] = $dbh;

            $connection_state = TRUE;

        } catch (\PDOException $pdo_e) {
            $message = "Unable to connect `{$dsl}`, PDO CONNECTION ERROR: " . $pdo_e->getMessage() . "\r\n" . PHP_EOL;

            $connection_state = [
                'error' => $message,
                'state' => FALSE
            ];

        } catch (\Exception $e) {
            $connection_state = [
                'error' => $e->getMessage(),
                'state' => FALSE
            ];
            self::$_configs[$config_key] = NULL;
        }

        if ($connection_state !== TRUE) {
            die($connection_state['error']);
        }

        self::$_configs[$config_key] = $config;
    }

    /**
     * Predicted (early) initialization
     *
     * $config must have:
     *  'hostname' (default localhost)
     *  'database' (default mysql)
     *  'username' (default root)
     *  'password' (default empty)
     *  'port' (default 3306)
     *
     * optional:
     *  'charset'
     *  'charset_collate'
     *
     *
     * @param null $suffix
     * @param $config
     */
    public static function init($suffix, $config)
    {
        $config_key = self::getKey($suffix);

        if (is_array($config)) {
            self::setConfig($config, $suffix);
            self::$_instances[$config_key] = (new self($suffix))->getInstance($suffix);
        } elseif (is_object($config)) {
            self::setConfig([], $suffix);
            self::$_instances[$config_key] = $config;
        } else {
            die(__METHOD__ . ' died: ' . PHP_EOL . print_r($config, true) . PHP_EOL . ' is not array or object! ');
        }
    }

    /**
     * Get connection config
     *
     * @param null $suffix
     * @return mixed|null
     */
    public static function getConfig($suffix = NULL): array
    {
        $config_key = self::getKey($suffix);
        return array_key_exists($config_key, self::$_configs) ? self::$_configs[$config_key] : NULL;
    }

    /**
     * Set connection config
     *
     * @param $config
     * @param null $suffix
     */
    public static function setConfig(array $config, $suffix = NULL)
    {
        $config_key = self::getKey($suffix);
        self::$_configs[$config_key] = $config;
    }

    /**
     * Alias: get PDO connection
     *
     * @param null $suffix
     * @return \PDO
     */
    public static function getConnection($suffix = NULL): \PDO
    {
        return self::getInstance($suffix);
    }


    /**
     * @param null $suffix
     * @return \PDO
     */
    public static function C($suffix = NULL): \PDO
    {
        return self::getConnection($suffix);
    }

    /*
    Set default connection context
    */

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
     */
    public static function getInstance($suffix = NULL):\PDO
    {
        $key = self::getKey($suffix);
        if (self::checkInstance($suffix)) {
            return self::$_instances[ $key ];
        }

        new self($suffix);
        return self::$_instances[ $key ];
    }

    /**
     * Get tables prefix for given connection
     *
     * @param null $suffix
     * @return null|string
     */
    public static function getTablePrefix($suffix = NULL):string
    {
        if (!self::checkInstance($suffix)) return NULL;

        $config_key = self::getKey($suffix);

        return
            array_key_exists('table_prefix', self::$_configs[$config_key] )
                ? self::$_configs[$config_key]['table_prefix']
                : '';
    }


    /**
     * @param $query
     * @param null $suffix
     * @return bool|false|\PDOStatement
     */
    public static function query($query, $suffix = NULL)
    {
        return DB::getConnection($suffix)->query($query);
    }

    /**
     * Get count(*) for given table
     *
     * @param $table
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCount($table, $suffix = NULL):int
    {
        if ($table == '') return null;
        $sth = self::getConnection($suffix)->query("SELECT COUNT(*) AS cnt FROM {$table}");

        return ($sth) ? $sth->fetchColumn() : null;
    }

    /**
     * Conditional getRowCount()
     * Аналог rowcound, только дает возможность выбрать поле выборки и условие
     *
     * @param $table
     * @param string $field
     * @param string $condition
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCountConditional($table, $field = '*', $condition = '', $suffix = NULL):int
    {
        if ($table === '') return null;

        $where = ($condition !== '') ? " WHERE {$condition} " : '';
        $field = ($field !== '*') ? "`{$field}`" : "*";

        $query = "SELECT COUNT({$field}) AS rowcount FROM {$table} {$where}";

        $sth = self::getConnection($suffix)->query($query);

        return ($sth) ? $sth->fetchColumn() : null;
    }

    /**
     * get Last Insert ID
     *
     * @param null $suffix
     * @return int
     */
    public static function getLastInsertId($suffix = NULL):int
    {
        return self::getConnection($suffix)->lastInsertId();
    }

    /**
     * Проверяет существование таблицы в БД
     *
     * @param string $table
     * @param null $suffix
     * @return bool
     * @throws \Exception
     */
    public static function checkTableExists($table = '', $suffix = NULL):bool
    {
        if (empty($table)) throw new \Exception(__CLASS__ . "::" . __METHOD__ . " -> table param empty");

        $query = "
SELECT *
FROM information_schema.tables
WHERE table_name LIKE ':table'
LIMIT 1;";
        $state = self::getConnection($suffix)->prepare($query);
        $state->execute(["table" => $table]);
        $result = $state->fetchColumn(2);

        if ($result && ($result === $table)) return true;
        return false;
    }



    /**
     * Build INSERT-query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @return string
     */
    public static function makeInsertQuery($tablename, $dataset):string
    {
        $set = [];
        if (empty($dataset)) {
            return "INSERT INTO {$tablename} () VALUES (); ";
        }

        $query = "INSERT INTO `{$tablename}` SET ";

        foreach ($dataset as $index=>$value) {
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
     * @param string $where_condition
     * @return bool|string
     */
    public static function makeUpdateQuery($tablename, $dataset, $where_condition = ''):string
    {
        $query = '';
        $r = [];

        if (empty($dataset))
            return false;

        $query = "UPDATE `{$tablename}` SET";

        foreach ($dataset as $index=>$value) {
            $r[] = "\r\n`{$index}` = :{$index}";
        }

        $query .= implode(', ', $r);

        $query .= " \r\n" . $where_condition . " ;";

        return $query;
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

        $query .= implode(', ', array_map(function ($i){
            return "`{$i}`";
        }, $dataset_keys));

        $query .= " ) VALUES ( ";

        $query .= implode(', ', array_map(function ($i) use ($mva_attributes, $dataset){
            return in_array($i, $mva_attributes) ? "({$dataset[$i]})" : ":{$i}";
        }, $dataset_keys));

        $query .= " ) ";

        $new_dataset = array_filter($dataset, function ($value, $key) use ($mva_attributes) {
            return !in_array($key, $mva_attributes);
        }, ARRAY_FILTER_USE_BOTH);

        return [
            $query, $new_dataset
        ];
    }


}

# -eof-
