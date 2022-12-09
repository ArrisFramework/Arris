<?php

namespace Arris\DB\PDOWrapper;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Class PDOWrapper
 * @package SteamBoat
 */
class PDOWrapperStatic implements PDOWrapperInterface
{
    /**
     * @var PDO
     */
    public static $dbh;
    
    /**
     * @var PDOStatement
     */
    public static $sth;
    /**
     * @var LoggerInterface
     */
    private static $logger;
    
    /**
     * @var float
     */
    private static $slow_query_threshold;
    
    private static $query_count;
    /**
     * @var float|string
     */
    private static $query_time;
    
    public static function init($pdo_connector, $options = [], LoggerInterface $logger = null)
    {
        self::$dbh = $pdo_connector;
        self::$logger = $logger;
        
        if (array_key_exists('slow_query_threshold', $options)) {
            self::$slow_query_threshold = (float)$options[ 'slow_query_threshold' ];
        }
    }
    
    public static function query(string $query, array $dataset)
    {
        $time_start = microtime(true);
        
        self::$sth = self::$dbh->prepare($query);
        
        foreach ($dataset as $key => $value) {
            if (is_array($value)) {
                $type = (count($value) > 1) ? $value[1] : PDO::PARAM_STR;
                self::$sth->bindValue($key, $value[0], $type);
            } else {
                self::$sth->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $execute_result = self::$sth->execute();
        
        $time_consumed = microtime(true) - $time_start;
    
        $debug = debug_backtrace()[1];
        $caller = sprintf("%s%s%s", ($debug['class'] ?? ''), ($debug['type'] ?? ''), ($debug['function'] ?? ''));
        
        if (!$execute_result) {
            self::$logger->error("PDO::execute() error: ", [
                $caller,
                ((PHP_SAPI === "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                self::$dbh->errorInfo(),
                $query
            ]);
        }
        
        if (($time_consumed >= self::$slow_query_threshold)) {
            self::$logger->info("PDO::execute() slow: ", [
                $time_consumed,
                $caller,
                ((PHP_SAPI === "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                $query
            ]);
        }
        
        self::$query_count++;
        self::$query_time += $time_consumed;
        
        return $execute_result;
    }
    
    public static function result()
    {
        return self::$sth;
    }
    
    public static function fetch($row = 0)
    {
        return (self::$sth instanceof PDOStatement) ? (self::$sth->fetchAll())[$row] : [];
    }
    
    public static function fetchRow($row = 0)
    {
        return (self::$sth instanceof PDOStatement) ? (self::$sth->fetchAll())[$row] : [];
    }
    
    public static function fetchColumn($column = 0, $default = null)
    {
        return (self::$sth instanceof PDOStatement) ? (self::$sth->fetchColumn($column)) : $default;
    }
    
    public static function fetchAll()
    {
        return (self::$sth instanceof PDOStatement) ? self::$sth->fetchAll() : [];
    }
    
    public static function fetchAllCallback($callback = null)
    {
        // was something like return (self::$sth instanceof PDOStatement) ? self::$sth->fetchAll(PDO::FETCH_CLASS, $class) : [];
        
        if (! self::$sth instanceof PDOStatement) {
            throw new PDOException("Internal sth is NOT instance of PDO. Probably called before ::query() method");
        }
        
        if (is_string($callback) || ($callback instanceof stdClass)) {
            return self::$sth->fetchAll(PDO::FETCH_CLASS, $callback);
        }
        
        if (is_callable($callback)) {
            while ($row = self::$sth->fetch()) {
                yield $callback($row);
            }
        }
        
        if (is_null($callback)) {
            return self::$sth->fetchAll();
        }
        
        return [];
    }
    
    public static function lastInsertID($name = null):string
    {
        return self::$dbh->lastInsertId($name);
    }
    
    public static function getStatistic()
    {
        return [
            'count' =>  self::$query_count,
            'time'  =>  self::$query_time
        ];
    }
    
    
}