<?php
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
    /**
     * Predicted (early) initialization
     *
     * @param null $suffix
     * @param $config
     */
    public static function init($suffix, array $config);

    /**
     * Get connection config
     *
     * @param null $suffix
     * @return mixed|null
     */
    public static function getConfig($suffix = NULL): array;

    /**
     * Set connection config
     *
     * @param array $config
     * @param null $suffix
     */
    public static function setConfig(array $config, $suffix = NULL);

    /**
     * Alias: get PDO connection
     *
     * @param null $suffix
     * @return \PDO
     */
    public static function getConnection($suffix = NULL): \PDO;

    /**
     * Set current connection key for internal calls === setContext() ?
     *
     * @param $suffix
     */
    public static function setConnection($suffix);

    /**
     * Get class instance == connection instance
     *
     * @param null $suffix
     * @return \PDO
     */
    public static function getInstance($suffix = NULL):\PDO;

    /**
     * Get tables prefix for given connection
     *
     * @param null $suffix
     * @return null|string
     */
    public static function getTablePrefix($suffix = NULL);

    /**
     *
     * @param $query
     * @param null $suffix
     * @return bool|\PDOStatement
     */
    public static function query($query, $suffix = NULL);

    /**
     * Get count(*) for given table
     *
     * @param $table
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCount($table, $suffix = NULL);

    /**
     * Аналог rowcound, только дает возможность выбрать поле выборки и условие
     *
     * @param $table
     * @param string $field
     * @param string $condition
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCountConditional($table, $field = '*', $condition = '', $suffix = NULL);

    /**
     * get Last Insert ID
     *
     * @param null $suffix
     */
    public static function getLastInsertId($suffix = NULL);

    /**
     * Build INSERT-query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @return string
     */
    public static function makeInsertQuery($tablename, $dataset);

    /**
     * Build UPDATE query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @param string $where_condition
     * @return bool|string
     */
    public static function makeUpdateQuery($tablename, $dataset, $where_condition = '');

    public static function BuildReplaceQuery(string $table, array $dataset);

    public static function BuildReplaceQueryMVA(string $table, array $dataset, array $mva_atrributes);

}

/**
 * Class DB
 */
class DB implements DBConnectionInterface
{
    const VERSION = '2.5';

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
    public static function init($suffix, array $config)
    {
        $config_key = self::getKey($suffix);
        self::setConfig($config, $suffix);
        self::$_instances[$config_key] = (new self($suffix))->getInstance($suffix);
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


    public static function setDefaultConnection($suffix)
    {
        self::$_current_connection = self::getKey($suffix);
    }

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
    public static function getTablePrefix($suffix = NULL)
    {
        if (!self::checkInstance($suffix)) return NULL;

        $config_key = self::getKey($suffix);

        return
            array_key_exists('table_prefix', self::$_configs[$config_key] )
                ? self::$_configs[$config_key]['table_prefix']
                : '';
    }


    /**
     *
     * @param $query
     * @param null $suffix
     * @return bool|\PDOStatement
     */
    public static function query($query, $suffix = NULL)
    {
        $state = FALSE;

        try {
            $state = DB::getConnection($suffix)->query($query);
        } catch (\PDOException $e) {

        }

        return $state;
    }

    /**
     * Get count(*) for given table
     *
     * @param $table
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCount($table, $suffix = NULL)
    {
        if ($table == '') return null;
        $sth = self::getConnection($suffix)->query("SELECT COUNT(*) AS cnt FROM {$table}");

        return ($sth) ? $sth->fetchColumn() : null;
    }

    /**
     * Conditional getRowCount()
     *
     * @param $table
     * @param string $field
     * @param string $condition
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCountConditional($table, $field = '*', $condition = '', $suffix = NULL)
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
     */
    public static function getLastInsertId($suffix = NULL)
    {
        self::getConnection($suffix)->lastInsertId();
    }

    /**
     * Проверяет существование таблицы в БД
     *
     * @param string $table
     * @param null $suffix
     * @return bool
     * @throws \Exception
     */
    public static function checkTableExists($table = '', $suffix = NULL)
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
    public static function makeInsertQuery($tablename, $dataset)
    {
        $query = '';
        $r = [];

        if (empty($dataset)) {
            $query = "INSERT INTO {$tablename} () VALUES (); ";
        } else {
            $query = "INSERT INTO `{$tablename}` SET ";

            foreach ($dataset as $index=>$value) {
                $r[] = "\r\n `{$index}` = :{$index}";
            }

            $query .= implode(', ', $r) . ' ;';
        }

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
    public static function makeUpdateQuery($tablename, $dataset, $where_condition = '')
    {
        $query = '';
        $r = [];

        if (empty($dataset)) {
            return FALSE;
        } else {
            $query = "UPDATE `{$tablename}` SET";

            foreach ($dataset as $index=>$value) {
                $r[] = "\r\n`{$index}` = :{$index}";
            }

            $query .= implode(', ', $r);

            $query .= " \r\n" . $where_condition . " ;";
        }

        return $query;
    }

    /**
     * Converts connection suffix to internal connection key
     *
     * @param null $suffix
     * @return string
     */
    private static function getKey($suffix = NULL)
    {
        return 'database' . ($suffix ? ":{$suffix}" : '');
    }

    /**
     * Check existance of connection in instances array
     *
     * @param null $suffix
     * @return bool
     */
    private static function checkInstance($suffix = NULL) {

        $key = self::getKey($suffix);
        return ( array_key_exists($key, self::$_instances) && self::$_instances[$key] !== NULL  );
    }


    public static function BuildReplaceQuery(string $table, array $dataset)
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
     * Применять как:
     *
     * list($update_query, $newdataset) = BuildReplaceQueryMVA($table, $original_dataset, $mva_attributes_list);
     * $update_statement = $sphinx->prepare($update_query);
     * $update_statement->execute($newdataset);
     *
     *
     * @param string $table             -- имя таблицы
     * @param array $dataset            -- сет данных.
     * @param array $mva_atrributes     -- массив с именами ключей MVA-атрибутов (они вставятся как значения, а не как placeholder-ы)
     * @return array                    -- возвращает массив с двумя значениями. Первый ключ - запрос, сет данных, очищенный от MVA-атрибутов.
     */
    public static function BuildReplaceQueryMVA(string $table, array $dataset, array $mva_atrributes)
    {
        $query = "REPLACE INTO `{$table}` (";

        $dataset_keys = array_keys($dataset);

        $query .= implode(', ', array_map(function ($i){
            return "`{$i}`";
        }, $dataset_keys));

        $query .= " ) VALUES ( ";

        $query .= implode(', ', array_map(function ($i) use ($mva_atrributes, $dataset){
            return in_array($i, $mva_atrributes) ? "({$dataset[$i]})" : ":{$i}";
        }, $dataset_keys));

        $query .= " ) ";

        $new_dataset = array_filter($dataset, function ($value, $key) use ($mva_atrributes) {
            return !in_array($key, $mva_atrributes);
        }, ARRAY_FILTER_USE_BOTH);

        return [
            $query, $new_dataset
        ];
    }


}
