# Result

- как добавить кастинг `toArray()` у экземпляра Result? 

# CLI Tables

https://packagist.org/packages/jc21/clitable -- выглядит отлично, но таблицы нужно именно _создавать_

https://packagist.org/packages/miloske85/php-cli-table - ?

https://packagist.org/packages/sevenecks/tableify - 

https://packagist.org/packages/pgooch/php-ascii-tables

# DB

А так?

```
DB::suffix('suffix')->config($config)->logger($logger)->options([])->init();
```

# OptionHelperClass

```php
$options = [];

Option::get($options)->key('xxx')->env('yyy')->_('zzz');

// что эквивалентно: setOption($options, 'xxx', 'yyy', 'zzz')

// опускаем вызов метода и получаем результат, эквивалетный опции null.

// или даже хелпер

Option($options)->key('xxx')->env('yyy')->_('zzz);

_('z') return 'z'

env('y') return getenv('y')

key('x') return если в массиве опций есть ключ x то его значение
 
if (getenv('y') !== false ) {
    return getenv('y')
} else {
    return $this; 
}

но это ничего не даст. Нужен дополнительный метод, который таки вернет результтт цепочки:

->get()

Как методу PHP определить, что он последний в цепочке вызовов и нужно вернуть значение?

Если только:

Option::_()->key()->env()->_()->from($options);

```

# FileSearchIterator

https://stackoverflow.com/a/18090806

```php
class FileSearchIterator extends FilterIterator
{
    protected $_path, $_extension, $_startWith, $_endWith;

    /**
     * Provides an iterator to a collection of filepath matching the given rules.
     *
     * If recursive is true also considers sub directories.
     *
     * Use in a foreach loop.
     *
     * @param string $path
     * @param boolean $recursive If set to true recursive sub directories are enumerated.
     * @param mixed $extension Either a single extension to accept or an array of such, null for no filter
     * @param mixed $startWith Either a single string that must appear at the beginning of the file name, an array of such or null.
     * @param mixed $endWith Either a single string that must appear at the end of the file name, an array of such or null.
     * @throws LogicException
     */
    public function __construct($path, $recursive = false, $extension = null, $startWith = null, $endWith = null)
    {
        if (!\is_dir($path)) {
            throw new LogicException("$path : is not a valid folder accessible to this process");
        }

        if (!is_array($extension)) {
            $extension = array($extension);
        }

        if (!is_array($startWith)) {
            $startWith = array($startWith);
        }

        if (!is_array($endWith)) {
            $endWith = array($endWith);
        }

        $this->_path = $path;
        $this->_extension = $extension;
        $this->_startWith = $startWith;
        $this->_endWith = $endWith;

        $it
            = $recursive
            ? new RecursiveDirectoryIterator($path, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_SELF)
            : new DirectoryIterator($path);

        if ($recursive) {
            parent::__construct(new RecursiveIteratorIterator($it));
        } else {
            parent::__construct($it);
        }
    }

    /**
     * Filter function implemented for the FilterIterator
     *
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function accept()
    {
        $info = $this->getInnerIterator()->current();

        if (!$info instanceof SplFileInfo) return false;
        if (!$info->isFile() || $info->isDot()) return false;

        $info = pathinfo($info->getFilename());

        if ($this->_extension[0] != null) {//only allow specified extensions
            if (!isset($info['extension']) || $info['extension'] == null) return false;
            $ext = $info['extension'];
            if (!in_array($ext, $this->_extension)) return false;
        }

        if ($this->_startWith[0] != null) {//string match from start of filename
            $found = false;
            $file = $info['filename'];

            foreach ($this->_startWith as $filter) {
                if (strncmp($file, $filter, strlen($filter)) == 0) {
                    $found = true;
                    break;
                }
            }

            if (!$found) return false;
        }

        if ($this->_endWith[0] != null) {//string match from end of filename
            $found = false;
            $file = $info['filename'];

            foreach ($this->_endWith as $filter) {
                if (substr_compare($file, $filter, -strlen($filter), strlen($filter)) === 0) {
                    $found = true;
                    break;
                }
            }

            if (!$found) return false;
        }

        return true;
    }
}

$fileScanner = new FileSearchIterator(__DIR__ . '/src/fonts', true, array('png'));
foreach($fileScanner as $file)
{
    /**
     * @var FilesystemIterator $file
     */
    $fonts[] = $file->getRealPath();
}

var_dump($fonts);

```

