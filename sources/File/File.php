<?php

namespace Arris\File;

/**
 * File Wrapper
 */
class File
{
    private $handler;
    private $last_io_content_length;

    public function __construct($filename, $mode)
    {
        $this->handler = fopen($filename, $mode);
        return $this;
    }

    public function close()
    {
        return fclose($this->handler);
    }

    public function write($data, $length = null)
    {
        if (!is_null($length)) {
            $result = fwrite($this->handler, $data, $length);
        } else {
            $result = fwrite($this->handler, $data);
        }
        $this->last_io_content_length = $result;
        return $this;
    }

}