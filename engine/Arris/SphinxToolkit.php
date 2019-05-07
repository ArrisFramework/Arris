<?php declare(strict_types=1);

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
}


use Closure;
use PDO;
use Arris\CLIConsole;

class SphinxToolkit implements SphinxToolkitInterface
{
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
        'sleep_time'            =>  1
    ];

    public function __construct(\PDO $mysql_connection, \PDO $sphinx_connection)
    {
        $this->mysql_connection = $mysql_connection;
        $this->sphinx_connection = $sphinx_connection;
    }

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
        return $this->rai_options;
    }

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

        if ($this->rai_options['log_total_rows_found'])
            CLIConsole::echo_status("<font color='green'>{$total_count}</font> rows found.");

        // iterate chunks
        for ($i = 0; $i < ceil($total_count / $chunk_size); $i++) {
            $offset = $i * $chunk_size;

            if ($this->rai_options['log_before_chunk'])
                CLIConsole::echo_status("Fetching rows from <font color='green'>{$offset}</font>, <font color='yellow'>{$chunk_size}</font> count.");

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

            if ($this->rai_options['log_after_chunk'])
                CLIConsole::echo_status("Updated RT-index <font color='yellow'></font>{$sphinx_index}</font>.");

            if ($this->rai_options['sleep_after_chunk']) {
                CLIConsole::echo_status("ZZZZzzz for {$this->rai_options['sleep_time']} seconds... ", FALSE);
                sleep($this->rai_options['sleep_time']);
                CLIConsole::echo_status("I woke up!");
            }


        } // for

        return $total_updated;
    } // function

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
    }


}