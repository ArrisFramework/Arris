<?php

namespace Arris\Database;

use IteratorAggregate;

/**
 * @method array        fetchAll()
 * @method mixed        fetch()
 * @method mixed        fetchColumn()
 * @method object|false fetchObject()
 *
 * @method bool         bindParam()
 * @method bool         bindColumn()
 * @method bool         bindValue()
 *
 * @method int          rowCount()
 * @method int          columnCount()
 *
 * @method string       errorCode()
 * @method array        errorInfo()
 *
 * @method bool         setAttribute()
 * @method mixed        getAttribute()
 *
 * @method array|false  getColumnMeta()
 * @method              setFetchMode()
 *
 * @method bool         nextRowset()
 * @method bool         closeCursor()
 *
 */
class PDOStatement
{
    public $PDOStatement;

    /**
     * @var DBConfig
     */
    public $config;

    public function __construct(\PDOStatement $PDOStatement, DBConfig $config)
    {
        $this->PDOStatement = $PDOStatement;
        $this->config = $config;
    }

    public function execute(array $input_parameters = null):bool
    {
        $before_call = microtime(true);
        $result = $this->PDOStatement->execute($input_parameters);
        $after_call = microtime(true);

        $this->config->total_time += $after_call - $before_call;
        $this->config->total_queries++;

        return $result;
    }

    public function __call($method, $args)
    {
        $before_call = microtime(true);
        $result = call_user_func_array(array($this->PDOStatement, $method), $args);
        $after_call = microtime(true);

        $this->config->total_time += $after_call - $before_call;
        $this->config->total_queries++;

        return $result;
    }

    public function __get($name)
    {
        return $this->PDOStatement->{$name};
    }

}
