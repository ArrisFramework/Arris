<?php

namespace Arris\DB;

interface SimpleQueryBuilderInterface {
    public function __construct();
    public function insert($table);
    public function replace($table);
    public function update($table);
    public function select($fields = null);
    public function from($table);
    public function where($where);
    public function data($data, $exclude = []);
    public function build();
}