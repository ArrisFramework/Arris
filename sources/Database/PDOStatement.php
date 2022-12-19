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
        $time_consumed = $after_call - $before_call;

        if ($time_consumed >= $this->config->slow_query_threshold) {
            $debug = debug_backtrace();
            $debug = $debug[1] ?? $debug[0];
            $caller = sprintf("%s%s%s", ($debug['class'] ?? ''), ($debug['type'] ?? ''), ($debug['function'] ?? ''));

            $this->config->logger->info("PDO::execute() slow: ", [
                $time_consumed,
                $caller,
                ((PHP_SAPI === "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']))
            ]);
        }

        $this->config->total_time += $after_call - $before_call;
        $this->config->total_queries++;

        return $result;
    }

    public function __call($method, $args)
    {
        $before_call = microtime(true);
        $result = call_user_func_array(array($this->PDOStatement, $method), $args);
        $after_call = microtime(true);
        $time_consumed = $after_call - $before_call;

        if ($time_consumed >= $this->config->slow_query_threshold) {
            $debug = debug_backtrace();
            $debug = $debug[1] ?? $debug[0];
            $caller = sprintf("%s%s%s", ($debug['class'] ?? ''), ($debug['type'] ?? ''), ($debug['function'] ?? ''));

            $this->config->logger->info("PDO::{$method} slow: ", [
                $time_consumed,
                $caller,
                ((PHP_SAPI === "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                $args
            ]);
        }

        $this->config->total_time += $time_consumed;
        $this->config->total_queries++;

        return $result;
    }

    public function __get($name)
    {
        return $this->PDOStatement->{$name};
    }

}
