<?php

/**
 * Класс, имплементирующий некоторые методы работы со сфинксом/мантикорой.
 *
 *
 */

namespace Arris;

interface SphinxToolkitInterface {

    /**
     * Устанавливает опции для перестроителя RT-индекса
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


use Closure;
use PDO;
use Arris\CLIConsole;

class SphinxToolkit
{
    const VERSION = "1.13";
    /**
     * @var \PDO
     */
    public $mysql_connection;

    /**
     * @var \PDO
     */
    public $sphinx_connection;

    private $rai_options = [
        'chunk_length'          =>  500,

        'log_rows_inside_chunk' =>  true,
        'log_total_rows_found'  =>  true,

        'log_before_chunk'      =>  true,
        'log_after_chunk'       =>  true,

        'sleep_after_chunk'     =>  true,
        'sleep_time'            =>  1,

        'log_before_index'      =>  true,
        'log_after_index'       =>  true,
    ];

    public function __construct(\PDO $mysql_connection, \PDO $sphinx_connection)
    {
        $this->mysql_connection = $mysql_connection;
        $this->sphinx_connection = $sphinx_connection;
    }

    /**
     * @param array $options
     * @return array
     */
    public function setRebuildIndexOptions(array $options = []):array
    {
        // на самом деле разворачиваем опции с установкой дефолтов
        $this->rai_options['chunk_length'] = isset($options['chunk_length']) ? $options['chunk_length'] : 500;

        $this->rai_options['log_rows_inside_chunk'] = isset($options['log_rows_inside_chunk']) ? $options['log_rows_inside_chunk'] : true;
        $this->rai_options['log_total_rows_found'] = isset($options['log_total_rows_found']) ? $options['log_total_rows_found'] : true;

        $this->rai_options['log_before_chunk'] = isset($options['log_before_chunk']) ? $options['log_before_chunk'] : true;
        $this->rai_options['log_after_chunk'] = isset($options['log_after_chunk']) ? $options['log_after_chunk'] : true;

        $this->rai_options['sleep_after_chunk'] = isset($options['sleep_after_chunk']) ? $options['sleep_after_chunk'] : true;
        $this->rai_options['sleep_time'] = isset($options['sleep_time']) ? $options['sleep_time'] : 1;

        $this->rai_options['log_before_index'] = isset($options['log_before_index']) ? $options['log_before_index'] : true;
        $this->rai_options['log_after_index'] = isset($options['log_after_index']) ? $options['log_after_index'] : true;

        return $this->rai_options;
    } // setRebuildIndexOptions

    /**
     *
     *
     * @param string $mysql_table
     * @param string $sphinx_index
     * @param Closure $make_updateset_method
     * @param string $condition
     * @return int
     */
    public function rebuildAbstractIndex(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = ''):int
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

                $update_query = DB::BuildReplaceQuery($sphinx_index, $update_set);

                $update_statement = $sphinx_connection->prepare($update_query);
                $update_statement->execute($update_set);
                $total_updated++;
            } // while

            if ($this->rai_options['log_after_chunk']) {
                CLIConsole::echo_status("Updated RT-index <font color='yellow'>{$sphinx_index}</font>.");
            } else {
                CLIConsole::echo_status("<strong>Ok</strong>");
            }

            if ($this->rai_options['sleep_after_chunk']) {
                CLIConsole::echo_status("ZZZZzzz for {$this->rai_options['sleep_time']} seconds... ", FALSE);
                sleep($this->rai_options['sleep_time']);
                CLIConsole::echo_status("I woke up!");
            }
        } // for
        if ($this->rai_options['log_after_index']) CLIConsole::echo_status("Total updated <strong>{$total_updated}</strong> elements for <font color='yellow'>{$sphinx_index}</font> RT-index.");

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

                list($update_query, $new_update_set) = DB::buildReplaceQueryMVA($sphinx_index, $update_set, $mva_indexes_list);

                $update_statement = $sphinx_connection->prepare($update_query);
                $update_statement->execute($new_update_set);
                $total_updated++;
            } // while

            if ($this->rai_options['log_after_chunk']) {
                CLIConsole::echo_status("Updated RT-index <font color='yellow'>{$sphinx_index}</font>.");
            } else {
                CLIConsole::echo_status("<strong>Ok</strong>");
            }

            if ($this->rai_options['sleep_after_chunk']) {
                CLIConsole::echo_status("ZZZZzzz for {$this->rai_options['sleep_time']} seconds... ", FALSE);
                sleep($this->rai_options['sleep_time']);
                CLIConsole::echo_status("I woke up!");
            }
        } // for
        if ($this->rai_options['log_after_index']) CLIConsole::echo_status("Total updated <strong>{$total_updated}</strong> elements for <font color='yellow'>{$sphinx_index}</font> RT-index.");

        return $total_updated;
    } // rebuildAbstractIndexMVA

    /**
     * @param PDO $mysql
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

        $target = self::mb_str_replace($needle, $opts['before_match'] . $needle . $opts['after_match'], $target);

        if (mb_strlen($source) > $opts['limit'] ) {
            $target = self::mb_trim_text($target, $opts['limit'] ,true,false, $opts['chunk_separator']);
        }

        return $target;
    } // EmulateBuildExcerpts

    /**
     * Multibyte string replace
     *
     * @param string|string[] $search  the string to be searched
     * @param string|string[] $replace the replacement string
     * @param string          $subject the source string
     * @param int             &$count  number of matches found
     *
     * @return string replaced string
     * @author Rodney Rehm, imported from Smarty
     *
     */
    private static function mb_str_replace($search, $replace, $subject, &$count = 0)
    {
        if (!is_array($search) && is_array($replace)) {
            return false;
        }
        if (is_array($subject)) {
            // call mb_replace for each single string in $subject
            foreach ($subject as &$string) {
                $string = self::mb_str_replace($search, $replace, $string, $c);
                $count += $c;
            }
        } elseif (is_array($search)) {
            if (!is_array($replace)) {
                foreach ($search as &$string) {
                    $subject = self::mb_str_replace($string, $replace, $subject, $c);
                    $count += $c;
                }
            } else {
                $n = max(count($search), count($replace));
                while ($n--) {
                    $subject = self::mb_str_replace(current($search), current($replace), $subject, $c);
                    $count += $c;
                    next($search);
                    next($replace);
                }
            }
        } else {
            $parts = mb_split(preg_quote($search), $subject);
            $count = count($parts) - 1;
            $subject = implode($replace, $parts);
        }
        return $subject;
    }

    /**
     * trims text to a space then adds ellipses if desired
     * @param string $input text to trim
     * @param int $length in characters to trim to
     * @param bool $ellipses if ellipses (...) are to be added
     * @param bool $strip_html if html tags are to be stripped
     * @param string $ellipses_text text to be added as ellipses
     * @return string
     *
     * http://www.ebrueggeman.com/blog/abbreviate-text-without-cutting-words-in-half
     *
     * еще есть вариант: https://stackoverflow.com/questions/8286082/truncate-a-string-in-php-without-cutting-words (но без обработки тегов)
     * https://www.php.net/manual/ru/function.wordwrap.php - см комментарии
     */
    private static function mb_trim_text($input, $length, $ellipses = true, $strip_html = true, $ellipses_text = '...')
    {
        //strip tags, if desired
        if ($strip_html) {
            $input = strip_tags($input);
        }

        //no need to trim, already shorter than trim length
        if (mb_strlen($input) <= $length) {
            return $input;
        }

        //find last space within length
        $last_space = mb_strrpos(mb_substr($input, 0, $length), ' ');
        $trimmed_text = mb_substr($input, 0, $last_space);

        //add ellipses (...)
        if ($ellipses) {
            $trimmed_text .= $ellipses_text;
        }

        return $trimmed_text;
    }

}