<?php
/*
  Статический класс, имплементирующий методы пересборки реалтайм-индексов к сфинксу.

  Включает в себя два метода rt_ReplaceIndex() и rt_DeleteIndex(), которые используются повсеместно в проектах.

  Работает через пакет Foolz\SphinxQL

  Не тестировался метод internal_TruncateIndex() - удаление записей индекса без WHERE

  2019-09-16 : Не тестировался в целом. Его имеет смысл использовать для единообразия кода.

Предполагаемый порядок вызова:

SphinxToolkitFoolz::init($host, $port, $options);
SphinxToolkitFoolz::initConnection();

SphinxToolkitFools::rt_RebuildAbstractIndex('articles', getenv('SPHINX_REALTIME_INDEX_ARTICLES'), function ($item){
        normalizeSerialData($item['photo']);
        normalizeSerialData($item['rubrics']);
        normalizeSerialData($item['districts']);

        return [
            'id'            =>  $item['id'],
            'type'          =>  1,
            'title'         =>  $item['title'],
            'short'         =>  $item['short'],
            'text'          =>  $item['text_bb'],
            'date_added'    =>  (DateTime::createFromFormat('Y-m-d H:i:s', $item['cdate']))->format('U'), // $item['cdate']  = 2019-04-09 23:49:00
            'photo'         =>  ((@$item['photo']['file'] != "") ? 1 : 0),
            'author'        =>  $item['author'],

            // 'districts_all' =>  $item['districts_all'],
            // 'rubrics'       =>  array_keys($item['rubrics']),
            // 'districts'     =>  array_keys($item['districts'])
        ];
    }, "s_hidden = 0 AND s_draft = 0")

 */

namespace Arris\Toolkit;

use Arris\CLIConsole;
use Closure;
use Foolz\SphinxQL\Drivers\Mysqli\Connection;
use Foolz\SphinxQL\Drivers\ResultSetInterface;
use Foolz\SphinxQL\Exception\DatabaseException;
use Foolz\SphinxQL\Helper;
use Foolz\SphinxQL\SphinxQL;

use function ArrisFrameWorkSetOption as setOption;

interface SphinxToolkitFoolzInterface {

    public static function init(string $sphinx_connection_host, string $sphinx_connection_port, $options = []);
    public static function initConnection();

    public static function rt_ReplaceIndex(string $index_name, array $updateset);
    public static function rt_DeleteIndex(string $index_name, string $field, $field_value = null);

    public static function rt_RebuildAbstractIndex(\PDO $pdo_connection, string $sql_source_table, string $sphinx_index, Closure $make_updateset_method, string $condition = '');

}

class SphinxToolkitFoolz
{
    /* ================== STATIC USAGE =================== */
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


    /**
     * Инициализация статического интерфейса к методам
     *
     * @param string $sphinx_connection_host
     * @param string $sphinx_connection_port
     * @param array $options
     */
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

    /**
     * Создает коннекшен для множественных обновлений (в крон-скриптах, к примеру, вызывается после init() )
     */
    public static function initConnection()
    {
        $conn = new Connection();
        $conn->setParams([
            'host' => self::$spql_connection_host,
            'port' => self::$spql_connection_port
        ]);

        self::$spql_connection = $conn;
    }

    /**
     * Получает инстанс (для множественных обновлений)
     *
     * @return SphinxQL
     */
    private static function getInstance()
    {
        return (new SphinxQL(self::$spql_connection));
    }

    /**
     * Создает инстанс SphinxQL (для однократного обновления)
     *
     * @return SphinxQL
     */
    private static function createInstance()
    {
        $conn = new Connection();
        $conn->setParams([
            'host' => self::$spql_connection_host,
            'port' => self::$spql_connection_port
        ]);

        return (new SphinxQL($conn));
    }

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
    public static function rt_ReplaceIndex(string $index_name, array $updateset)
    {
        if (empty($updateset)) return null;

        return self::createInstance()
            ->replace()
            ->into($index_name)
            ->set($updateset)
            ->execute();
    }

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
    public static function rt_DeleteIndex(string $index_name, string $field, $field_value = null)
    {
        if (is_null($field_value)) return null;

        return self::createInstance()
            ->delete()
            ->from($index_name)
            ->where($field, '=', $field_value)
            ->execute();
    }

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
            CLIConsole::echo_status("<font color='yellow'>[{$sphinx_index}]</font> index : ", false);

        if (self::$rlo['log_total_rows_found'])
            CLIConsole::echo_status("<font color='green'>{$total_count}</font> elements found for rebuild.");

        // iterate chunks
        for ($i = 0; $i < ceil($total_count / $chunk_size); $i++) {
            $offset = $i * $chunk_size;

            if (self::$rlo['log_before_chunk']) CLIConsole::echo_status("Rebuilding elements from <font color='green'>{$offset}</font>, <font color='yellow'>{$chunk_size}</font> count... " , false);

            $query_chunk_data = "SELECT * FROM {$sql_source_table} ";
            $query_chunk_data.= $condition != '' ? " WHERE {$condition} " : ' ';
            $query_chunk_data.= "ORDER BY id DESC LIMIT {$offset}, {$chunk_size} ";

            $sth = $pdo_connection->query($query_chunk_data);

            // iterate inside chunk
            while ($item = $sth->fetch()) {
                if (self::$rlo['log_rows_inside_chunk'])
                    CLIConsole::echo_status("{$sql_source_table}: {$item['id']}");

                $update_set = $make_updateset_method($item);

                self::internal_ReplaceIndex($sphinx_index, $update_set);

                $total_updated++;
            } // while

            $breakline_after_chunk = !self::$rlo['sleep_after_chunk'];

            if (self::$rlo['log_after_chunk']) {
                CLIConsole::echo_status("Updated RT-index <font color='yellow'>{$sphinx_index}</font>.", $breakline_after_chunk);
            } else {
                CLIConsole::echo_status("<strong>Ok</strong>", $breakline_after_chunk);
            }

            if (self::$rlo['sleep_after_chunk']) {
                CLIConsole::echo_status("  ZZZZzzz for " . self::$rlo['sleep_time'] . " second(s)... ", FALSE);
                sleep(self::$rlo['sleep_time']);
                CLIConsole::echo_status("I woke up!");
            }
        } // for
        if (self::$rlo['log_after_index'])
            CLIConsole::echo_status("Total updated <strong>{$total_updated}</strong> elements for <font color='yellow'>{$sphinx_index}</font> RT-index. <br>");

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

# -eof-
