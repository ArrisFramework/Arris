<?php declare(strict_types=1);

/**
 * Класс, имплементирующий некоторые методы работы со сфинксом/мантикорой.
 *
 *
 */

namespace Arris;

interface SphinxToolkitInterface {
    public function rt_RebuildAbstractIndex(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = '', $chunk_length = 500);
}


use Closure;
use PDO;

use Arris\DB;

class SphinxToolkit
{
    /**
     * @var \PDO
     */
    public $mysql_connection;

    /**
     * @var \PDO
     */
    public $sphinx_connection;

    public function __construct(\PDO $mysql_connection, \PDO $sphinx_connection)
    {
        $this->mysql_connection = $mysql_connection;
        $this->sphinx_connection = $sphinx_connection;
    }


    public function rt_RebuildAbstractIndex(string $mysql_table, string $sphinx_index, Closure $make_updateset_method, string $condition = '', $chunk_length = 500)
    {
        $mysql_connection = $this->mysql_connection;
        $sphinx_connection = $this->sphinx_connection;

        $chunk_size = $chunk_length;

        // truncate
        $sphinx_connection->query("TRUNCATE RTINDEX {$sphinx_index}");

        // get total count
        $total_count = $this->mysql_GetRowCount($mysql_connection, $mysql_table, $condition);
        $total_updated = 0;

        // iterate chunks
        for ($i = 0; $i < ceil($total_count / $chunk_size); $i++) {
            $offset = $i * $chunk_size;

            $query_chunk_data = "SELECT * FROM {$mysql_table} ";
            $query_chunk_data.= $condition != '' ? " WHERE {$condition} " : '';
            $query_chunk_data.= "ORDER BY id DESC LIMIT {$offset}, {$chunk_size} ";

            $sth = $mysql_connection->query($query_chunk_data);

            // iterate inside chunk
            while ($item = $sth->fetch()) {
                echo "{$mysql_table}: {$item['id']}", PHP_EOL;

                $update_set = $make_updateset_method($item);

                $update_query = DB::BuildReplaceQuery($sphinx_index, $update_set);

                $update_statement = $sphinx_connection->prepare($update_query);
                $update_statement->execute($update_set);
                $total_updated++;
            } // while
        } // for

        return $total_updated;
    }

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