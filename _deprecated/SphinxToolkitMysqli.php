<?php

/**
 * Динамический класс, имплементирующий методы пересборки реалтайм-индексов к сфинксу.
 *
 * Работает через самописные (но работающие) методы обновления индексов через MySQLi драйвер.
 *
 * Это быстрый, но довольно сложный для рефакторинга метод доступа через PDO, кроме того
 * для обновления MULTI-VALUED-атрибутов используется довольно сложный механизм превращения их в численные значения
 * (а не строки)
 *
 *

 Синтаксис, к примеру, такой:

 $mysql_connection = DB::getConnection();
 $sphinx_connection = DB::getConnection('SPHINX');
 $toolkit = new SphinxToolkit($mysql_connection, $sphinx_connection);
 $toolkit->setRebuildIndexOptions([
    'log_rows_inside_chunk' =>  false,
    'log_after_chunk'       =>  false,
    'sleep_after_chunk'     =>  $options['is_sleep'],
    'sleep_time'            =>  $options['sleeptime'],
    'chunk_length'          =>  $options['sql_limit']
 ]);

$toolkit->rebuildAbstractIndexMVA('articles', getenv('SPHINX_REALTIME_INDEX_ARTICLES'), function ($item){
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
            // 'rubrics'       =>  implode(',', array_keys($item['rubrics'])),
            // 'districts'     =>  implode(',', array_keys($item['districts']))
        ];
    }, "s_hidden = 0 AND s_draft = 0", ['rubrics', 'districts']);

 *
 *
 */


namespace Arris\Toolkit;

use Arris\CLIConsole;
use Arris\DB;
use Closure;

use function ArrisFramework__SetOption as setOption;
use function ArrisFramework__mb_trim_text as mb_trim_text;
use function ArrisFramework__mb_str_replace as mb_str_replace;

interface SphinxToolkitMysqliInterface {

    public function __construct(\PDO $mysql_connection, \PDO $sphinx_connection);
    public function setRebuildIndexOptions(array $options = []):array;

    public function rebuildAbstractIndex(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = ''):int;
    public function rebuildAbstractIndexMVA(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = '', array $mva_indexes_list = []):int;

    public static function EmulateBuildExcerpts($source, $needle, $options);

}

class SphinxToolkitMysqli
{
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

    /**
     * SphinxToolkit constructor.
     *
     * @param \PDO $mysql_connection
     * @param \PDO $sphinx_connection
     */
    public function __construct(\PDO $mysql_connection, \PDO $sphinx_connection)
    {
        $this->mysql_connection = $mysql_connection;
        $this->sphinx_connection = $sphinx_connection;
    }

    /**
     * Устанавливает опции для перестроителя RT-индекса
     * @param array $options - новый набор опций
     * @return array - результирующий набор опций
     */
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

    /**
     * Перестраивает RT-индекс
     *
     * @param string $mysql_table -- SQL-таблица исходник
     * @param string $sphinx_index -- имя индекса (таблицы)
     * @param Closure $make_updateset_method - замыкание, анонимная функция, преобразующая исходный набор данных в то, что вставляется в индекс
     * @param string $condition -- условие выборки из исходной таблицы (без WHERE !!!)
     * @return int -- количество обновленных записей в индексе
     */

    public function rebuildAbstractIndex(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = ''):int
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
        $sphinx_connection->query("TRUNCATE RTINDEX '{$sphinx_index}' ");

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

                $update_query = DB::buildReplaceQuery($sphinx_index, $update_set);

                $update_statement = $sphinx_connection->prepare($update_query);
                $update_statement->execute($update_set);
                $total_updated++;
            } // while

            $breakline_after_chunk = !$this->rai_options['sleep_after_chunk'];

            if ($this->rai_options['log_after_chunk']) {
                CLIConsole::say("Updated RT-index <font color='yellow'>{$sphinx_index}</font>.", $breakline_after_chunk);
            } else {
                CLIConsole::say("<strong>Ok</strong>", $breakline_after_chunk);
            }

            if ($this->rai_options['sleep_after_chunk']) {
                CLIConsole::say("ZZZZzzz for {$this->rai_options['sleep_time']} second(s)... ", FALSE);
                sleep($this->rai_options['sleep_time']);
                CLIConsole::say("I woke up!");
            }
        } // for
        if ($this->rai_options['log_after_index'])
            CLIConsole::say("Total updated <strong>{$total_updated}</strong> elements for <font color='yellow'>{$sphinx_index}</font> RT-index. <br>");

        return $total_updated;
    } // rebuildAbstractIndex

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
    public function rebuildAbstractIndexMVA(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = '', array $mva_indexes_list = []):int
    {
        $mysql_connection = $this->mysql_connection;
        $sphinx_connection = $this->sphinx_connection;

        $chunk_size = $this->rai_options['chunk_length'];

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
    public static function EmulateBuildExcerpts($source, $needle, $options)
    {
        $opts = [
            // Строка, вставляемая перед ключевым словом. По умолчанию "<strong>".
            'before_match' => '<strong>',

            // Строка, вставляемая после ключевого слова. По умолчанию "</strong>".
            'after_match' => '</strong>',

            // Строка, вставляемая между частями фрагмента. по умолчанию "...".
            'chunk_separator' => '...',

            // дальнейшие опции не реализованы в эмуляции

            // НЕ РЕАЛИЗОВАНО: Максимальный размер фрагмента в символах. Integer, по умолчанию 256.
            'limit'     => 256,

            // НЕ РЕАЛИЗОВАНО: Сколько слов необходимо выбрать вокруг каждого совпадающего с ключевыми словами блока. Integer, по умолчанию 5.
            'around'    => 5,

            // НЕ РЕАЛИЗОВАНО: Необходимо ли подсвечивать только точное совпадение с поисковой фразой, а не отдельные ключевые слова. Boolean, по умолчанию FALSE.
            "exact_phrase"  => null,

            // НЕ РЕАЛИЗОВАНО: Необходимо ли извлечь только единичный наиболее подходящий фрагмент. Boolean, по умолчанию FALSE.
            "single_passage"    =>  null
        ];

        if (is_array($options)) {
            foreach ($opts as $key_name => $key_value) {
                if (array_key_exists($key_name, $options)) {
                    $opts[ $key_name ] = $options[ $key_name ];
                }
            }
        }

        $target = strip_tags($source);

        $target = mb_str_replace($needle, $opts['before_match'] . $needle . $opts['after_match'], $target);

        if (mb_strlen($source) > $opts['limit'] ) {
            $target = mb_trim_text($target, $opts['limit'] ,true,false, $opts['chunk_separator']);
        }

        return $target;
    } // EmulateBuildExcerpts

}

# -eof-
