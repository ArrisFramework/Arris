<?php


namespace ArrisDeprecated;


class DataMapper
{
    private $source;

    private $rules = [

    ];
    private $key; // current key (fast access)

    public function __construct($source, $logger = null, $options = [])
    {
        $this->source = $source;
    }

    /**
     * FLOW-правило, указывает на значение по ключу
     *
     * ? + добавить вторым аргументом дефолтное значение?
     *
     * @param $key
     * @return $this
     */
    public function key($key)
    {
        $this->rules['key'] = $key;
        $this->key = $key;
        return $this;
    }

    /**
     * Указывает, требуется ли ключ.
     *
     * ? Исключение, если ключ не задавался ?
     *
     * @param bool $isRequired
     * @return $this
     */
    public function required($isRequired = true)
    {
        $this->rules['value.required'] = $isRequired;
        return $this;
    }

    public function default($arg)
    {
        $this->rules['value.default'] = $arg;
        return $this;
    }


    /**
     * FLOW-правило, указывает, что из SOURCE надо удалить значение
     */
    public function drop()
    {
        $this->rules['drop.after'] = true;
        return $this;
    }

    /*
     * Финализатор PUT
     */
    public function put(&$target)
    {
        $target = $this->__cookMapping();
        $this->rules = [];
        return true;
    }

    /**
     * Финализатор GET
     *
     * @return mixed
     */
    public function get()
    {
        $result = $this->__cookMapping();
        $this->rules = [];
        return $result;
    }

    /* ===== ПРИВАТНЫЕ МЕТОДЫ ===== */

    private function __cookMapping()
    {
        $this->__validateRules();

        $value
            = array_key_exists($this->key, $this->source)
            ? $this->source[ $this->key ]
            : $this->rules['default'];

        if ($this->rules['drop.after']) {
            unset($this->source [ $this->key ]);
        }

        $this->__clean();
    }

    /**
     *
     */
    private function __validateRules()
    {
        if (!array_key_exists('key', $this->source))
            throw new \InvalidArgumentException("No key for this chain present");
    }

    private function __clean()
    {
        unset($this->key);
        $this->rules = [];
    }

}

$v = new DataMapper($_REQUEST);

$text_bb = $v->key('text_bb')->required(false)->get();
$text = $v->key('text')->required()->default('Something')->drop()->get();
