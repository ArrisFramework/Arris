<?php

namespace Arris\DB;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Class PDOWrapper
 * @package SteamBoat
 */
class PDOWrapper
{
    private static $global_query_count;
    private static $global_query_time;
    
    private $query_count = 0;
    private $query_time = 0;
    
    /**
     * @var PDO
     */
    private $dbh;
    
    /**
     * @var LoggerInterface|null
     */
    private $logger;
    
    /**
     * @var float
     */
    private $slow_query_threshold;
    
    /**
     * @var PDOStatement
     */
    private $sth;
    
    public function __construct($pdo_connector, $options = [], LoggerInterface $logger = null)
    {
        $this->dbh = $pdo_connector;
        $this->logger = $logger;
    
        if (array_key_exists('slow_query_threshold', $options)) {
            $this->slow_query_threshold = (float)$options[ 'slow_query_threshold' ];
        }
    }
    
    public function __destruct()
    {
        self::$global_query_count += $this->query_count;
        self::$global_query_time += $this->query_time;
    }
    
    public function query(string $query, array $dataset)
    {
        $time_start = microtime(true);
        
        $this->sth = $this->dbh->prepare($query);
        
        foreach ($dataset as $key => $value) {
            if (is_array($value)) {
                $type = (count($value) > 1) ? $value[1] : PDO::PARAM_STR;
                $this->sth->bindValue($key, $value[0], $type);
            } else {
                $this->sth->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $execute_result = $this->sth->execute();
        
        $time_consumed = microtime(true) - $time_start;
    
        $debug = debug_backtrace()[1];
        $caller = sprintf("%s%s%s", ($debug['class'] ?? ''), ($debug['type'] ?? ''), ($debug['function'] ?? ''));
        
        if (!$execute_result) {
            $this->logger->error("PDO::execute() error: ", [
                $caller,
                ((PHP_SAPI === "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                $this->dbh->errorInfo(),
                $query
            ]);
        }
        
        if (($time_consumed >= $this->slow_query_threshold)) {
            $this->logger->info("PDO::execute() slow: ", [
                $time_consumed,
                $caller,
                ((PHP_SAPI === "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                $query
            ]);
        }
        
        $this->query_count++;
        $this->query_time += $time_consumed;
        
        return $execute_result;
    }
    
    public function result()
    {
        return $this->sth;
    }
    
    public function fetch($row = 0)
    {
        return ($this->sth instanceof PDOStatement) ? ($this->sth->fetchAll())[$row] : [];
    }
    
    public function fetchRow($row = 0)
    {
        return ($this->sth instanceof PDOStatement) ? ($this->sth->fetchAll())[$row] : [];
    }
    
    public function fetchColumn($column = 0, $default = null)
    {
        return ($this->sth instanceof PDOStatement) ? ($this->sth->fetchColumn($column)) : $default;
    }
    
    public function fetchAll()
    {
        return ($this->sth instanceof PDOStatement) ? $this->sth->fetchAll() : [];
    }
    
    public function fetchAllCallback($callback = null)
    {
        if (! $this->sth instanceof PDOStatement) {
            throw new PDOException("Internal sth is NOT instance of PDO. Probably called before ::query() method");
        }
        
        if (is_string($callback) || ($callback instanceof stdClass)) {
            return $this->sth->fetchAll(PDO::FETCH_CLASS, $callback);
        }
        
        if (is_callable($callback)) {
            while ($row = $this->sth->fetch()) {
                yield $callback($row);
            }
        }
        
        if (is_null($callback)) {
            return $this->sth->fetchAll();
        }
        
        return [];
    }
    
    public function lastInsertID($name = null):string
    {
        return $this->dbh->lastInsertId($name);
    }
    
    public static function getStatistic()
    {
        return [
            'count' =>  self::$global_query_count,
            'time'  =>  self::$global_query_time
        ];
    }
    
    
}