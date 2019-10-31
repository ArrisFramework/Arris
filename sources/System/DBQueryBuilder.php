<?php

namespace Arris\System;

use Arris\DBQueryBuilderInterface;

class DBQueryBuilder implements DBQueryBuilderInterface
{
    /**
     * @var string
     */
    private $method;
    private $table;
    private $where;
    private $dataset;
    private $select_fields;

    public function __construct()
    {
        return $this;
    }

    public function insert($table)
    {
        $this->method = 'INSERT';
        $this->table = $table;
        return $this;
    }

    public function replace($table)
    {
        $this->method = 'REPLACE';
        $this->table = $table;
        return $this;
    }

    public function update($table)
    {
        $this->method = 'UPDATE';
        $this->table = $table;
        return $this;
    }

    public function select($fields = null)
    {
        $this->method = 'SELECT';
        if (is_array($fields)) {
            $this->select_fields = implode(',', $fields);
        } elseif (is_string($fields) && strtoupper($fields) !== '*') {
            $this->select_fields = " $fields ";
        } else {
            $this->select_fields = ' * ';
        }
        return $this;
    }

    public function from($table)
    {
        $this->method = 'SELECT';
        $this->table = $table;

        return $this;
    }

    public function where($where)
    {
        if (is_array($where)) {
            foreach ($where as $cond) {
                $this->where[] = $cond;
            }
            $this->where = implode(' AND ', $this->where);

        } elseif (is_string($where)) {
            $this->where = $where;
        } else {
            $this->where = NULL;
        }

        if (!is_null($this->where)) {
            $this->where = " WHERE {$this->where}";
        }

        return $this;
    }

    public function data($data)
    {
        $this->dataset = $data;
        return $this;
    }

    public function build()
    {
        $sql = '';

        switch ($this->method) {
            case 'INSERT': {
                $sql = $this->buildInsertQuery($this->table, $this->dataset);
                break;
            }
            case 'UPDATE': {
                $sql = $this->buildUpdateQuery($this->table, $this->dataset, $this->where);
                break;
            }
            case 'REPLACE': {
                $sql = $this->buildReplaceQuery($this->table, $this->dataset, $this->where);
                break;
            }
            case 'SELECT': {
                $sql = " SELECT {$this->select_fields} FROM {$this->table} {$this->where} ";
            }
        }
        return $sql;
    }


    private function buildInsertQuery($table, &$dataset)
    {
        if (empty($dataset)) {
            return "INSERT INTO {$table} () VALUES (); ";
        }

        $fields = [];

        $query = "INSERT INTO `{$table}` SET ";

        foreach ($dataset as $index => $value) {
            if (strtoupper(trim($value)) === 'NOW()') {
                $fields[] = "\r\n `{$index}` = NOW()";
                unset($dataset[ $index ]);
                continue;
            }

            $fields[] = "\r\n `{$index}` = :{$index}";
        }

        $query .= implode(', ', $fields) . ' ;';

        return $query;
    }

    private function buildUpdateQuery($table, &$dataset, $where = '')
    {
        $fields = [];

        if (empty($dataset))
            return false;

        $query = "UPDATE `{$table}` SET";

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

    private function buildReplaceQuery($table, &$dataset, $where = '')
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

# -eof-
