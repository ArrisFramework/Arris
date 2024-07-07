<?php

namespace Arris;

/**
 * Class Path
 *
 * Immutable
 *
 * @package Arris
 */
class Path implements PathInterface
{
    public array $atoms = [];
    
    public $hasTrailingSeparator;
    
    public $isAbsolutePath;
    
    public function validateAtom($value)
    {
        if (is_string($value)) {
            if ($value === '') {
                return null;
            }
            
            if ($value === '.') {
                $value = '';
            }
            
            return $value;
        }
        
        if (is_array($value)) {
            return self::create($value)->toString();
        }
        
        if ($value instanceof PathInterface) {
            return $value->__toString();
        }
        
        return $value;
    }

    /**
     * Экспорт экземпляра Path в строку
     *
     * @param null $hasTrailingSeparator
     * @return string
     */
    private function export($hasTrailingSeparator = null)
    {
        // если переданный аргумент НЕ null - он перекрывает предыдущие настройки
        if (!is_null($hasTrailingSeparator)) {
            $this->hasTrailingSeparator = (bool)$hasTrailingSeparator;
        }
        $hasTS = $hasTrailingSeparator ? DIRECTORY_SEPARATOR : '';

        $isAP = $this->isAbsolutePath ? DIRECTORY_SEPARATOR : '';

        $path
            = $isAP
            . implode(self::ATOM_SEPARATOR, $this->atoms)
            . $hasTS;
        
        if (strpos($path, ':||') !== false) {
            $path = preg_replace("#:\|\|#", '://', $path);
        } else {
            $path = preg_replace('#' . self::ATOM_SEPARATOR .'+#', self::ATOM_SEPARATOR, $path);
        }
        
        return $path;
    }
    
    /**
     * @return string
     */
    public function toString($hasTrailingSeparator = false)
    {
        return $this->export($hasTrailingSeparator);
    }
    
    /**
     * Magic __toString method
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
    
    /**
     * Мутирует текущий инстанс - устанавливает флаг "завершающий слэш"
     *
     * @param bool $is_present
     * @return $this
     */
    public function setTrailingSeparator($is_present = true):Path
    {
        $this->hasTrailingSeparator = (bool)$is_present;
        return $this;
    }

    /**
     * Мутирует текущий инстанс - устанавливает флаг "абсолютный путь (начинается с /)"
     *
     * @param bool $is_present
     * @return $this
     */
    public function setAbsolutePath($is_present = true)
    {
        $this->isAbsolutePath = (bool)$is_present;
        return $this;
    }

    /**
     * Мутирует инстанс - устанавливает опции
     *
     * @param array $options
     * @return $this
     */
    public function setOptions($options = [])
    {
        if (array_key_exists('isAbsolute', $options)) {
            $this->isAbsolutePath = (bool)$options['isAbsolute'];
        }
        if (array_key_exists('hasTrailingSeparator', $options)) {
            $this->hasTrailingSeparator = (bool)$options['hasTrailingSeparator'];
        }
        
        return $this;
    }

    /**
     * Create ummutable instance
     *
     * @param Path|string|array $path
     *
     * @param null $isAbsolutePath
     * @param null $hasTrailingSeparator
     * @return Path
     */
    public static function create($path, $isAbsolutePath = null, $hasTrailingSeparator = null): Path
    {
        if ($path instanceof Path) {
            $path = $path->toString(true);
        }

        return new self($path, $isAbsolutePath, $hasTrailingSeparator);
    }
    
    /**
     * Path constructor
     *
     * Что делать с попыткой склейки URL-схемы через Path?
     * a) правильный путь: сделать свой класс для склейки URL-ов
     * б) костыль: если есть схема - менять :// на :|| (в имени нормального файла такого не бывает), а при выводе строки менять обратно.
     *    Используем костыль
     *
     * @param $path - строка | массив строк | массив элементов (строка / экземпляр PathInterface)
     */
    public function __construct($path, $isAbsolutePath = null, $hasTrailingSeparator = null)
    {
        $atoms = [];
        
        if (is_string($path)) {
            if (preg_match('#://#', $path) === 1) {
                $path = preg_replace("#://#", ':||', $path);
            }
            
            // теперь все таки заменяем `//` на `/`
            $path = preg_replace("#/+#", "/", $path);
            
            if ('' === $path) {
                // $path = self::SELF_ATOM;
                $isAbsolutePath = true;
            }
            
            $path = explode(self::ATOM_SEPARATOR, $path);
        }

        $numAtoms = count($path);
        
        if ($numAtoms > 1) {
            if ('' === $path[0] ) {
                $isAbsolutePath = true;
                array_shift($path);
                --$numAtoms;
            }
            
            if ('' === $path[$numAtoms - 1]) {
                $hasTrailingSeparator = (!$isAbsolutePath || $numAtoms > 1);
                array_pop($path);
            } else {
                $hasTrailingSeparator = false;
            }
        }
        
        
        foreach ($path as $path_atom) {
            $_atom = $this->validateAtom($path_atom);
            
            if (!is_null($_atom)) {
                $atoms[] = $_atom;
            }
        }
        
        $this->atoms = $atoms;
        $this->isAbsolutePath = $isAbsolutePath;
        $this->hasTrailingSeparator = $hasTrailingSeparator;
    }
    
    
    /**
     * @param $data
     * @return $this|Path
     */
    public function join($data)
    {
        // или приводить к строке, или делать array_merge atoms with [ data ]
        /*if ($data instanceof Path) {
            $data = $data->toString();
        }*/

        if (is_string($data)) {
            $data = explode(DIRECTORY_SEPARATOR, $data);
        }

        return new self(array_merge($this->atoms, [ $data ]), $this->isAbsolutePath, $this->hasTrailingSeparator);
    }
    
    /**
     * @param $data
     * @return $this|Path
     */
    public function joinName($data)
    {
        if (is_string($data)) {
            $data = explode(DIRECTORY_SEPARATOR, $data);
        }
        
        return new self(array_merge($this->atoms, [ $data ]), $this->isAbsolutePath, false);
    }
    
    /**
     *
     * @return bool
     */
    public function isPresent():bool
    {
        return is_dir($this->toString());
    }

    /**
     * Проверяет наличие файла
     *
     * @return bool
     */
    public function isFile():bool
    {
        $fn = $this->toString();
        return is_file($fn) && is_readable($fn);
    }
    
    /**
     *
     * @param int $access_rights
     * @return bool
     */
    public function makePath($access_rights = 0777):bool
    {
        $path = $this->toString(); // не нужно?
        
        return is_dir( $path ) || ( mkdir( $path, 0777, true ) && is_dir( $path ) );
    }


    
}

# -eof-
