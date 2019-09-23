<?php

namespace Arris\Toolkit;

use Closure;

use Arris\DB;
use Arris\CLIConsole;

use Foolz\SphinxQL\Drivers\Mysqli\Connection;
use Foolz\SphinxQL\Drivers\ResultSetInterface;
use Foolz\SphinxQL\Exception\DatabaseException;
use Foolz\SphinxQL\Helper;
use Foolz\SphinxQL\SphinxQL;

use function Arris\setOption as setOption;
use function Arris\mb_trim_text as mb_trim_text;
use function Arris\mb_str_replace as mb_str_replace;

/**
 * Interface __SphinxToolkitFoolzInterface
 *
 *
 *
 * @package Arris\Toolkit
 */
interface __SphinxToolkitFoolzInterface {

    /**
     * Инициализация статического интерфейса к методам
     *
     * @param string $sphinx_connection_host
     * @param string $sphinx_connection_port
     * @param array $options
     */
    public static function init(string $sphinx_connection_host, string $sphinx_connection_port, $options = []);

    /**
     * Создает коннекшен для множественных обновлений (в крон-скриптах, к примеру, вызывается после init() )
     */
    public static function initConnection();

    /**
     * Создает инстанс SphinxQL (для однократного обновления)
     *
     * @return SphinxQL
     */
    public static function createInstance();

    /**
     * Обновляет (REPLACE) реалтайм-индекс по набору данных
     * с созданием коннекшена "сейчас"
     *
     * @param string $index_name
     * @param array $updateset
     * @return ResultSetInterface|null
     *
     * @throws DatabaseException
     * @throws \Foolz\SphinxQL\Exception\ConnectionException
     * @throws \Foolz\SphinxQL\Exception\SphinxQLException
     */
    public static function rt_ReplaceIndex(string $index_name, array $updateset);

    /**
     * Удаляет строку реалтайм-индекса
     * с созданием коннекшена "сейчас"
     *
     * @param string $index_name        -- индекс
     * @param string $field             -- поле для поиска индекса
     * @param null $field_value         -- значение для поиска индекса
     * @return ResultSetInterface|null
     *
     * @throws DatabaseException
     * @throws \Foolz\SphinxQL\Exception\ConnectionException
     * @throws \Foolz\SphinxQL\Exception\SphinxQLException
     */
    public static function rt_DeleteIndex(string $index_name, string $field, $field_value = null);

    /**
     * @param \PDO $pdo_connection
     * @param string $sql_source_table
     * @param string $sphinx_index
     * @param Closure $make_updateset_method
     * @param string $condition
     * @return int
     * @throws DatabaseException
     * @throws \Foolz\SphinxQL\Exception\ConnectionException
     * @throws \Foolz\SphinxQL\Exception\SphinxQLException
     */
    public static function rt_RebuildAbstractIndex(\PDO $pdo_connection, string $sql_source_table, string $sphinx_index, Closure $make_updateset_method, string $condition = '');

    /**
     * Получает инстанс (для множественных обновлений)
     *
     * @return SphinxQL
     */
    public static function getInstance();
}

/**
 * Interface __SphinxToolkitMysqliInterface
 *
 *
 *
 * @package Arris\Toolkit
 */
interface __SphinxToolkitMysqliInterface {

    /**
     * SphinxToolkit constructor.
     *
     * @param \PDO $mysql_connection
     * @param \PDO $sphinx_connection
     */
    public function __construct(\PDO $mysql_connection, \PDO $sphinx_connection);

    /**
     * Устанавливает опции для перестроителя RT-индекса
     *
     * @param array $options - новый набор опций
     * @return array - результирующий набор опций
     */
    public function setRebuildIndexOptions(array $options = []):array;

    /**
     * Перестраивает RT-индекс
     *
     * @param string $mysql_table -- SQL-таблица исходник
     * @param string $sphinx_index -- имя индекса (таблицы)
     * @param Closure $make_updateset_method - замыкание, анонимная функция, преобразующая исходный набор данных в то, что вставляется в индекс
     * @param string $condition -- условие выборки из исходной таблицы (без WHERE !!!)
     * @return int -- количество обновленных записей в индексе
     */
    public function rebuildAbstractIndex(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = ''):int;

    /**
     *
     *
     * @param string $mysql_table               -- SQL-таблица исходник
     * @param string $sphinx_index              -- имя индекса (таблицы)
     * @param Closure $make_updateset_method    -- замыкание, анонимная функция, преобразующая исходный набор данных в то, что вставляется в индекс
     * @param string $condition                 -- условие выборки из исходной таблицы (без WHERE !!!)
     * @param array $mva_indexes_list           -- список MVA-индексов, значения которых не нужно биндить через плейсхолдеры
     *
     * @return int
     */
    public function rebuildAbstractIndexMVA(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = '', array $mva_indexes_list = []):int;

    /**
     * Эмулирует BuildExcerpts из SphinxAPI
     *
     * @param $source
     * @param $needle
     * @param $options
     * 'before_match' => '<strong>',    // Строка, вставляемая перед ключевым словом. По умолчанию "<strong>".
     * 'after_match' => '</strong>',    // Строка, вставляемая после ключевого слова. По умолчанию "</strong>".
     * 'chunk_separator' => '...',      // Строка, вставляемая между частями фрагмента. по умолчанию "...".
     *
     * опции 'limit', 'around', 'exact_phrase' и 'single_passage' в эмуляции не реализованы
     *
     * @return mixed
     */
    public static function EmulateBuildExcerpts($source, $needle, $options);
}

class SphinxToolkit implements __SphinxToolkitMysqliInterface, __SphinxToolkitFoolzInterface
{
    /* =========================== DYNAMIC IMPLEMENTATION ================================ */
    /**
     * @var array
     */
    private $rai_options;

    /**
     * @var \PDO
     */
    private $mysql_connection;

    /**
     * @var \PDO
     */
    private $sphinx_connection;

    public function __construct(\PDO $mysql_connection, \PDO $sphinx_connection)
    {
        $this->mysql_connection = $mysql_connection;
        $this->sphinx_connection = $sphinx_connection;
    }


    public function setRebuildIndexOptions(array $options = []):array
    {
        // на самом деле разворачиваем опции с установкой дефолтов
        $this->rai_options['chunk_length'] = setOption($options, 'chunk_length', null, 500);

        $this->rai_options['log_rows_inside_chunk'] = setOption($options, 'log_rows_inside_chunk', null, true);
        $this->rai_options['log_total_rows_found'] = setOption($options, 'log_total_rows_found', null, true);

        $this->rai_options['log_before_chunk'] = setOption($options, 'log_before_chunk', null, true);
        $this->rai_options['log_after_chunk'] = setOption($options, 'log_after_chunk', null, true);

        $this->rai_options['sleep_after_chunk'] = setOption($options, 'sleep_after_chunk', null, true);

        $this->rai_options['sleep_time'] = setOption($options, 'sleep_time', null, 1);
        if ($options['sleep_time'] == 0) {
            $options['sleep_after_chunk'] = false;
        }

        $this->rai_options['log_before_index'] = setOption($options, 'log_before_index', null, true);
        $this->rai_options['log_after_index'] = setOption($options, 'log_after_index', null, true);

        return $this->rai_options;
    } // setRebuildIndexOptions


    public function rebuildAbstractIndex(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = ''):int
    {
        $mysql_connection = $this->mysql_connection;
        $sphinx_connection = $this->sphinx_connection;

        $this->checkIndexExist($sphinx_index);

        // проверяем, существует ли индекс
        $index_definition = $sphinx_connection->query("SHOW TABLES LIKE '{$sphinx_index}' ")->fetchAll();

        if (count($index_definition) == 0 ) {
            return -1;
        }

        $chunk_size = $this->rai_options['chunk_length'];

        // truncate
        $sphinx_connection->query("TRUNCATE RTINDEX {$sphinx_index} ");

        // get total count
        $total_count = $this->mysql_GetRowCount($mysql_connection, $mysql_table, $condition);
        $total_updated = 0;

        if ($this->rai_options['log_before_index'])
            CLIConsole::echo_status("<font color='yellow'>[{$sphinx_index}]</font> index : ", false);

        if ($this->rai_options['log_total_rows_found'])
            CLIConsole::echo_status("<font color='green'>{$total_count}</font> elements found for rebuild.");

        // iterate chunks
        for ($i = 0; $i < ceil($total_count / $chunk_size); $i++) {
            $offset = $i * $chunk_size;

            if ($this->rai_options['log_before_chunk'])
                CLIConsole::echo_status("Rebuilding elements from <font color='green'>{$offset}</font>, <font color='yellow'>{$chunk_size}</font> count... " , false);

            $query_chunk_data = "SELECT * FROM {$mysql_table} ";
            $query_chunk_data.= $condition != '' ? " WHERE {$condition} " : '';
            $query_chunk_data.= "ORDER BY id DESC LIMIT {$offset}, {$chunk_size} ";

            $sth = $mysql_connection->query($query_chunk_data);

            // iterate inside chunk
            while ($item = $sth->fetch()) {
                if ($this->rai_options['log_rows_inside_chunk'])
                    CLIConsole::echo_status("{$mysql_table}: {$item['id']}");

                $update_set = $make_updateset_method($item);

                $update_query = DB::buildReplaceQuery($sphinx_index, $update_set);

                $update_statement = $sphinx_connection->prepare($update_query);
                $update_statement->execute($update_set);
                $total_updated++;
            } // while

            $breakline_after_chunk = !$this->rai_options['sleep_after_chunk'];

            if ($this->rai_options['log_after_chunk']) {
                CLIConsole::echo_status("Updated RT-index <font color='yellow'>{$sphinx_index}</font>.", $breakline_after_chunk);
            } else {
                CLIConsole::echo_status("<strong>Ok</strong>", $breakline_after_chunk);
            }

            if ($this->rai_options['sleep_after_chunk']) {
                CLIConsole::echo_status("ZZZZzzz for {$this->rai_options['sleep_time']} second(s)... ", FALSE);
                sleep($this->rai_options['sleep_time']);
                CLIConsole::echo_status("I woke up!");
            }
        } // for
        if ($this->rai_options['log_after_index'])
            CLIConsole::echo_status("Total updated <strong>{$total_updated}</strong> elements for <font color='yellow'>{$sphinx_index}</font> RT-index. <br>");

        return $total_updated;
    } // rebuildAbstractIndex


    public function rebuildAbstractIndexMVA(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = '', array $mva_indexes_list = []):int
    {
        $mysql_connection = $this->mysql_connection;
        $sphinx_connection = $this->sphinx_connection;

        $chunk_size = $this->rai_options['chunk_length'];

        // проверяем, существует ли индекс
        $index_definition = $sphinx_connection->query("SHOW TABLES LIKE '{$sphinx_index}' ")->fetchAll();

        if (count($index_definition) == 0 ) {
            return -1;
        }

        // truncate
        $sphinx_connection->query("TRUNCATE RTINDEX {$sphinx_index} ");

        // get total count
        $total_count = $this->mysql_GetRowCount($mysql_connection, $mysql_table, $condition);
        $total_updated = 0;

        if ($this->rai_options['log_before_index'])
            CLIConsole::say("<font color='yellow'>[{$sphinx_index}]</font> index : ", false);

        if ($this->rai_options['log_total_rows_found'])
            CLIConsole::say("<font color='green'>{$total_count}</font> elements found for rebuild.");

        // iterate chunks
        for ($i = 0; $i < ceil($total_count / $chunk_size); $i++) {
            $offset = $i * $chunk_size;

            if ($this->rai_options['log_before_chunk'])
                CLIConsole::say("Rebuilding elements from <font color='green'>{$offset}</font>, <font color='yellow'>{$chunk_size}</font> count... " , false);

            $query_chunk_data = "SELECT * FROM {$mysql_table} ";
            $query_chunk_data.= $condition != '' ? " WHERE {$condition} " : '';
            $query_chunk_data.= "ORDER BY id DESC LIMIT {$offset}, {$chunk_size} ";

            $sth = $mysql_connection->query($query_chunk_data);

            // iterate inside chunk
            while ($item = $sth->fetch()) {
                if ($this->rai_options['log_rows_inside_chunk'])
                    CLIConsole::say("{$mysql_table}: {$item['id']}");

                $update_set = $make_updateset_method($item);

                list($update_query, $new_update_set) = DB::buildReplaceQueryMVA($sphinx_index, $update_set, $mva_indexes_list);

                $update_statement = $sphinx_connection->prepare($update_query);
                $update_statement->execute($new_update_set);
                $total_updated++;
            } // while

            $breakline_after_chunk = !$this->rai_options['sleep_after_chunk'];

            if ($this->rai_options['log_after_chunk']) {
                CLIConsole::say("Updated RT-index <font color='yellow'>{$sphinx_index}</font>.", $breakline_after_chunk);
            } else {
                CLIConsole::say("<strong>Ok</strong>", $breakline_after_chunk);
            }

            if ($this->rai_options['sleep_after_chunk']) {
                CLIConsole::say("  ZZZZzzz for {$this->rai_options['sleep_time']} second(s)... ", FALSE);
                sleep($this->rai_options['sleep_time']);
                CLIConsole::say("I woke up!");
            }
        } // for
        if ($this->rai_options['log_after_index'])
            CLIConsole::say("Total updated <strong>{$total_updated}</strong> elements for <font color='yellow'>{$sphinx_index}</font> RT-index. <br>");

        return $total_updated;
    } // rebuildAbstractIndexMVA

    /**
     * @param \PDO $mysql
     * @param string $table
     * @param string $condition
     * @return int
     */
    private function mysql_GetRowCount(\PDO $mysql, string $table, string $condition)
    {
        $query = "SELECT COUNT(*) AS cnt FROM {$table}";
        if ($condition != '') $query .= " WHERE {$condition}";

        return $mysql->query($query)->fetchColumn();
    } // mysql_GetRowCount

    public function checkIndexExist(string $sphinx_index)
    {
        $index_definition = $this->sphinx_connection->query("SHOW TABLES LIKE '{$sphinx_index}' ")->fetchAll();

        return count($index_definition) > 0;
    }

    /* =========================== СТАТИЧЕСКИЕ МЕТОДЫ ==================================== */

    public static function EmulateBuildExcerpts($source, $needle, $options)
    {
        $opts = [
            // Строка, вставляемая перед ключевым словом. По умолчанию "<strong>".
            'before_match'  =>  setOption($options, 'before_match', null, '<strong>'),

            // Строка, вставляемая после ключевого слова. По умолчанию "</strong>".
            'after_match'   =>  setOption($options, 'after_match', null, '</strong>'),
            // Строка, вставляемая между частями фрагмента. по умолчанию "...".
            'chunk_separator' => '...',

            // НЕ РЕАЛИЗОВАНО: Максимальный размер фрагмента в символах. Integer, по умолчанию 256.
            'limit'         =>  setOption($options, 'limit', null, 256),

            // НЕ РЕАЛИЗОВАНО: Сколько слов необходимо выбрать вокруг каждого совпадающего с ключевыми словами блока. Integer, по умолчанию 5.
            'around'         =>  setOption($options, 'around', null, 5),

            // НЕ РЕАЛИЗОВАНО: Необходимо ли подсвечивать только точное совпадение с поисковой фразой, а не отдельные ключевые слова. Boolean, по умолчанию FALSE.
            'exact_phrase'         =>  setOption($options, 'around', null, null),

            // НЕ РЕАЛИЗОВАНО: Необходимо ли извлечь только единичный наиболее подходящий фрагмент. Boolean, по умолчанию FALSE.
            'single_passage'         =>  setOption($options, 'single_passage', null, null),

        ];

        $target = strip_tags($source);

        $target = mb_str_replace($needle, $opts['before_match'] . $needle . $opts['after_match'], $target);

        if (($opts['limit'] > 0) && ( mb_strlen($source) > $opts['limit'] )) {
            $target = mb_trim_text($target, $opts['limit'] ,true,false, $opts['chunk_separator']);
        }

        return $target;
    } // EmulateBuildExcerpts

    /* =========================== STATIC IMPLEMENTATION ================================= */

    /**
     * rebuild_logging_options
     *
     * @var array
     */
    private static $rlo = [];

    /**
     * @var Connection
     */
    private static $spql_connection_host;

    /**
     * @var Connection
     */
    private static $spql_connection_port;
    /**
     * @var Connection
     */
    private static $spql_connection;

    public static function init(string $sphinx_connection_host, string $sphinx_connection_port, $options = [])
    {
        self::$spql_connection_host = $sphinx_connection_host;
        self::$spql_connection_port = $sphinx_connection_port;

        self::$rlo['chunk_length']          = setOption($options, 'chunk_length', null, 500);

        self::$rlo['log_rows_inside_chunk'] = setOption($options, 'log_rows_inside_chunk', null, true);
        self::$rlo['log_total_rows_found']  = setOption($options, 'log_total_rows_found', null, true);

        self::$rlo['log_before_chunk']      = setOption($options, 'log_before_chunk', null, true);
        self::$rlo['log_after_chunk']       = setOption($options, 'log_after_chunk', null, true);

        self::$rlo['sleep_after_chunk']     = setOption($options, 'sleep_after_chunk', null, true);

        self::$rlo['sleep_time'] = setOption($options, 'sleep_time', null, 1);
        if ($options['sleep_time'] == 0) {
            $options['sleep_after_chunk'] = false;
        }

        self::$rlo['log_before_index']      = setOption($options, 'log_before_index', null, true);
        self::$rlo['log_after_index']       = setOption($options, 'log_after_index', null, true);
    }

    public static function initConnection()
    {
        $conn = new Connection();
        $conn->setParams([
            'host' => self::$spql_connection_host,
            'port' => self::$spql_connection_port
        ]);

        self::$spql_connection = $conn;
    }

    public static function getInstance()
    {
        return (new SphinxQL(self::$spql_connection));
    }

    public static function createInstance()
    {
        $conn = new Connection();
        $conn->setParams([
            'host' => self::$spql_connection_host,
            'port' => self::$spql_connection_port
        ]);

        return (new SphinxQL($conn));
    }

    public static function rt_ReplaceIndex(string $index_name, array $updateset)
    {
        if (empty($updateset)) return null;

        return self::createInstance()
            ->replace()
            ->into($index_name)
            ->set($updateset)
            ->execute();
    }

    public static function rt_DeleteIndex(string $index_name, string $field, $field_value = null)
    {
        if (is_null($field_value)) return null;

        return self::createInstance()
            ->delete()
            ->from($index_name)
            ->where($field, '=', $field_value)
            ->execute();
    }

    public static function rt_RebuildAbstractIndex(\PDO $pdo_connection, string $sql_source_table, string $sphinx_index, Closure $make_updateset_method, string $condition = '')
    {
        $chunk_size = self::$rlo['chunk_length'];

        self::internal_TruncateIndex($sphinx_index);

        $total_count
            = $pdo_connection
            ->query("SELECT COUNT(*) as cnt FROM {$sql_source_table} " . ($condition != '' ? " WHERE {$condition} " : ' ') )
            ->fetchColumn();
        $total_updated = 0;

        if (self::$rlo['log_before_index'])
            CLIConsole::say("<font color='yellow'>[{$sphinx_index}]</font> index : ", false);

        if (self::$rlo['log_total_rows_found'])
            CLIConsole::say("<font color='green'>{$total_count}</font> elements found for rebuild.");

        // iterate chunks
        for ($i = 0; $i < ceil($total_count / $chunk_size); $i++) {
            $offset = $i * $chunk_size;

            if (self::$rlo['log_before_chunk']) CLIConsole::say("Rebuilding elements from <font color='green'>{$offset}</font>, <font color='yellow'>{$chunk_size}</font> count... " , false);

            $query_chunk_data = "SELECT * FROM {$sql_source_table} ";
            $query_chunk_data.= $condition != '' ? " WHERE {$condition} " : ' ';
            $query_chunk_data.= "ORDER BY id DESC LIMIT {$offset}, {$chunk_size} ";

            $sth = $pdo_connection->query($query_chunk_data);

            // iterate inside chunk
            while ($item = $sth->fetch()) {
                if (self::$rlo['log_rows_inside_chunk'])
                    CLIConsole::say("{$sql_source_table}: {$item['id']}");

                $update_set = $make_updateset_method($item);

                self::internal_ReplaceIndex($sphinx_index, $update_set);

                $total_updated++;
            } // while

            $breakline_after_chunk = !self::$rlo['sleep_after_chunk'];

            if (self::$rlo['log_after_chunk']) {
                CLIConsole::say("Updated RT-index <font color='yellow'>{$sphinx_index}</font>.", $breakline_after_chunk);
            } else {
                CLIConsole::say("<strong>Ok</strong>", $breakline_after_chunk);
            }

            if (self::$rlo['sleep_after_chunk']) {
                CLIConsole::say("  ZZZZzzz for " . self::$rlo['sleep_time'] . " second(s)... ", FALSE);
                sleep(self::$rlo['sleep_time']);
                CLIConsole::say("I woke up!");
            }
        } // for
        if (self::$rlo['log_after_index'])
            CLIConsole::say("Total updated <strong>{$total_updated}</strong> elements for <font color='yellow'>{$sphinx_index}</font> RT-index. <br>");

        return $total_updated;
    }

    /**
     *
     * @param string $index_name
     */
    private static function internal_TruncateIndex(string $index_name)
    {
        (new Helper(self::$spql_connection))->truncateRtIndex($index_name);
    }

    /**
     *
     * @param string $index_name
     * @param array $updateset
     * @return ResultSetInterface|null
     * @throws DatabaseException
     * @throws \Foolz\SphinxQL\Exception\ConnectionException
     * @throws \Foolz\SphinxQL\Exception\SphinxQLException
     */
    private static function internal_ReplaceIndex(string $index_name, array $updateset)
    {
        if (empty($updateset)) return null;

        return self::getInstance()
            ->replace()
            ->into($index_name)
            ->set($updateset)
            ->execute();
    }


}