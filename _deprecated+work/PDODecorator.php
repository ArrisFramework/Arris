<?php

namespace Arris\DB;

use PDO;
use PDOStatement;

class PDODecorator
{
    /**
     * @var PDO $pdo
     */
    private $pdo;
    
    /**
     * Sets attribute
     *
     * @param int $attribute Attribute code
     * @param mixed $value
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        return $this->getPDO()->setAttribute($attribute, $value);
    }
    
    /**
     * Get the PDO object
     *
     * @return PDO
     */
    abstract protected function getPDO();
    
    /**
     * Returns attribute
     *
     * @param int $attribute Attribute code
     * @return mixed
     */
    public function getAttribute($attribute)
    {
        return $this->getPDO()->getAttribute($attribute);
    }
    
    /**
     * Checks if inside a transaction
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->getPDO()->inTransaction();
    }
    
    /**
     * Initiates a transaction
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->getPDO()->beginTransaction();
    }
    
    /**
     * Commits a transaction
     *
     * @return bool
     */
    public function commit()
    {
        return $this->getPDO()->commit();
    }
    
    /**
     * Rolls back a transaction
     *
     * @return bool
     */
    public function rollBack()
    {
        return $this->getPDO()->rollBack();
    }
    
    /**
     * Returns the SQLSTATE associated with the last operation on the database handle
     *
     * @return mixed
     */
    public function errorCode()
    {
        return $this->getPDO()->errorCode();
    }
    
    /**
     * Fetch extended error information associated with the last operation on the database handle
     *
     * @return array
     */
    public function errorInfo()
    {
        return $this->getPDO()->errorInfo();
    }
    
    /**
     * Execute an SQL statement and return the number of affected rows
     *
     * @param string $statement
     * @return int
     */
    public function exec($statement)
    {
        return $this->getPDO()->exec($statement);
    }
    
    /**
     * Prepares a statement for execution and returns a statement object
     *
     * @param string $statement
     * @param array $options
     * @return PDOStatement
     */
    public function prepare($statement, $options = array())
    {
        return $this->getPDO()->prepare($statement, $options);
    }
    
    /**
     * Quotes a string for use in a query.
     *
     * @param string $string
     * @param int $type
     * @return string
     */
    public function quote($string, $type = PDO::PARAM_STR)
    {
        return call_user_func_array(array($this->getPDO(), __FUNCTION__), func_get_args());
    }
    
    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @param string $name
     * @return string
     */
    public function lastInsertId($name = null)
    {
        return $this->getPDO()->lastInsertId($name);
    }
    
    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object,
     * overloading is supported
     *
     * @param string $statement
     * @return PDOStatement
     */
    public function query($statement)
    {
        return call_user_func_array(array($this->getPDO(), __FUNCTION__), func_get_args());
    }
    
    
    
}