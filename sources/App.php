<?php

namespace Arris;

use Adbar\Dot;

class App
{
    use Singleton;
    
    /**
     * @var Dot
     */
    private $repo = null;
    
    public function init($options)
    {
        if (is_null($this->repo)) {
            $this->repo = new Dot($options);
        } elseif (!empty($options)) {
            $this->repo->add($options);
        }
    }
    
    public function set($key, $data)
    {
        $this->repo[ $key ] = $data;
    }
    
    public function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->repo->get();
        }
        
        // return array_key_exists($key, $this->repo) ? $this->repo[ $key ] : $default;
        return $this->repo->get($key, $default);
    }
    
}