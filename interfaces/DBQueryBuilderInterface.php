<?php

namespace Arris;

interface DBQueryBuilderInterface {
    public function __construct();
    public function insert($table);
    public function replace($table);
    public function update($table);
    public function select($fields = null);
    public function from($table);
    public function where($where);
    public function data($data);
    public function build();
}