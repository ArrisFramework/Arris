<?php


namespace Arris;


class DBNew
{



    /**
     * Возвращает новый датасет, индекс для строк которого равен значению колонки строки
     * Предназначен для переформатирования PDO-ответов, полученных в режиме FETCH_ASSOC
     *
     * [ 0 => [ 'id' => 5, 'data' => 10], 1 => [ 'id' => 6, 'data' => 12] .. ]
     * При вызове с аргументами ($dataset, 'id') возвращает
     * [ 5 => [ 'id' => 5, 'data' => 10], 6 => [ 'id' => 6, 'data' => 12] .. ]
     *
     * @param $dataset
     * @param $column_id
     * @return array
     */
    public static function groupDatasetByColumn($dataset, $column_id)
    {
        $result = [];
        array_map(function ($row) use (&$result, $column_id){
            $result[ $row[ $column_id ] ] = $row;
        }, $dataset);
        return $result;
    }

}