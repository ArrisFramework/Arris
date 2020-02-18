<?php

class DB_NewF {

    /**
     * Замена функции DB::buildReplaceQuery() , обрабатывает поля со значением NOW()
     * Убирает их из набора данных
     *
     * @param $table
     * @param $dataset
     * @param string $where
     * @return bool|string
     */
    private static function buildReplaceQuery($table, &$dataset, $where = '')
    {
        $fields = [];

        if (empty($dataset))
            return false;

        $query = "REPLACE `{$table}` SET";

        foreach ($dataset as $index => $value) {
            if (strtoupper(trim($value)) === 'NOW()') {
                $fields[] = "\r\n `{$index}` = NOW()";
                unset($dataset[ $index ]);
                continue;
            }

            $fields[] = "\r\n`{$index}` = :{$index}";
        }

        $query .= implode(', ', $fields);

        $query .= " \r\n" . $where . " ;";

        return $query;
    }

}

