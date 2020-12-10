<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class Test {
    use \Arris\Traits\Singleton;
    
    private $data = [];
    
    public function init()
    {
        $this->data['initial'] = 5;
    }
    
    public function set($k, $v)
    {
        $this->data[ $k ] = $v;
    }
    
    public function get($k, $d = null)
    {
        return $this->data[$k] ?? $d;
    }
}

$t = Test::getInstance();

$t->set('foo', 45);

var_dump($t->get('foo'));