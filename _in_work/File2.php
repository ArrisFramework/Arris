<?php

namespace Arris;

class FileTest
{
    public $error = false;
    public $success = true;

    /**
     * @var resource
     */
    protected $file = null;

    public static function open($filename, $mode)
    {
        return new self($filename, $mode);
    }

    public function __constructor($filename, $mode)
    {
        $file = fopen($filename, $mode);

        if ($file === false) {
            throw new \RuntimeException("Open file error");
        }

        $this->file = $file;
    }

    public function close()
    {
        if (is_resource($this->file)) {
            fclose($this->file);
        }
    }

    public function bwrite(string $content, ?int $length)
    {
        if (!is_resource($this->file)) {
            throw new \RuntimeException("Can't write data to file");
        }

        $written = fwrite($this->file, $content, $length);

        if ($written == false) {
            throw new \RuntimeException();
        }
    }

}


$f = \Arris\FileTest::open('aaa.txt', '');