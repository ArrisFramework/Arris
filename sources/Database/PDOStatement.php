<?php

namespace Arris\Database;

use IteratorAggregate;

/**
 * @method array        fetchAll()
 * @method mixed        fetch(int $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, int $cursorOrientation = \PDO::FETCH_ORI_NEXT, int $cursorOffset = 0)
 * @method mixed        fetchColumn(int $mode = \PDO::FETCH_COLUMN)
 * @method object|false fetchObject(?string $class = "stdClass", array $constructorArgs = [])
 *
 * @method bool         bindParam(string|int $param, mixed &$var, int $type = \PDO::PARAM_STR, int $maxLength = 0, mixed $driverOptions = null)
 * @method bool         bindColumn(string|int $param, mixed &$var, int $type = \PDO::PARAM_STR, int $maxLength = 0, mixed $driverOptions = null)
 * @method bool         bindValue(string|int $param, mixed $value, int $type = \PDO::PARAM_STR)
 *
 * @method int          rowCount()
 * @method int          columnCount()
 *
 * @method string       errorCode()
 * @method array        errorInfo()
 *
 * @method bool         setAttribute(int $attribute, mixed $value)
 * @method mixed        getAttribute(int $name)
 *
 * @method array|false  getColumnMeta(int $column)
 * @method              setFetchMode(int $mode)
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

        if ($time_consumed >= $this->config->slow_query_threshold && $this->config->slow_query_threshold > 0) {
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

        if ($time_consumed >= $this->config->slow_query_threshold && $this->config->slow_query_threshold > 0) {
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
