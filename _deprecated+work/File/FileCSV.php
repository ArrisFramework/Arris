<?php

namespace Arris\File;

/**
 * CSV File Wrapper
 *
 * @todo: Arris 2.0+
 */
class FileCSV
{
    private $f;

    /**
     * @var string
     */
    private $separator;

    /**
     * @var string
     */
    private $enclosure;

    /**
     * @var string
     */
    private $escape;

    /**
     * @var string
     */
    private $eol;

    /**
     * @param $filename
     * @param string $mode
     * @param string $separator
     * @param string $enclosure
     * @param string $escape
     * @param string $eol
     */
    public function __constructor($filename, string $mode = 'w+', string $separator = ";", string $enclosure = "\"", string $escape = "\\", string $eol = "\n")
    {
        /*
         * Данная функция также может принимать директории в качестве параметра filename.
         * Если вы не знаете, является ли filename файлом или директорией, то вам может понадобиться использовать функцию is_dir() до вызова функции fopen().
         */
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->f = fopen($filename, $mode);

        if ($this->f === false) {
            throw new \RuntimeException("Unable create file {$filename} with mode {$mode}");
        }

        $this->separator = $separator;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        $this->eol = $eol;
    }

    /**
     * @param $data
     */
    public function put($data)
    {
        fputcsv($this->f, $data, $this->separator, $this->enclosure, $this->escape, $this->eol); //@note: EOL available since PHP 8.1.0
    }

    /**
     * @param int $length
     * @return false|int
     */
    public function get(int $length = 0)
    {
        return fgetcsv($this->f, $length, $this->separator, $this->enclosure, $this->escape, $this->eol); //@note: EOL available since PHP 8.1.0
    }

    /**
     *
     */
    public function close()
    {
        fclose($this->f);
    }

}