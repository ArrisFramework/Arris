<?php

namespace Arris\Core\Config;

use Arris\Core\Config\Exception\FileNotFoundException;
use Arris\Core\Config\Exception\UnsupportedFormatException;
use Arris\Core\Config\Exception\EmptyDirectoryException;
use Arris\Core\Config\Exception\WriteException;
use Arris\Core\Config\Parser\ParserInterface;
use Arris\Core\Config\Writer\WriterInterface;

/**
 * Configuration reader and writer for PHP.
 */
class Config extends AbstractConfig
{
    /**
     * All formats supported by Config.
     *
     * @var array
     */
    protected $supportedParsers = [
        'Arris\Core\Config\Parser\Php',
        'Arris\Core\Config\Parser\Ini',
        'Arris\Core\Config\Parser\Json',
        'Arris\Core\Config\Parser\Xml',
        'Arris\Core\Config\Parser\Yaml',
        'Arris\Core\Config\Parser\Properties',
        'Arris\Core\Config\Parser\Serialize'
    ];

    /**
     * All formats supported by Config.
     *
     * @var array
     */
    protected $supportedWriters = [
        'Arris\Core\Config\Writer\Ini',
        'Arris\Core\Config\Writer\Json',
        'Arris\Core\Config\Writer\Xml',
        'Arris\Core\Config\Writer\Yaml',
        'Arris\Core\Config\Writer\Properties',
        'Arris\Core\Config\Writer\Serialize'
    ];

    /**
     * Static method for loading a Config instance.
     *
     * @param  string|array    $values Filenames or string with configuration
     * @param  ParserInterface $parser Configuration parser
     * @param  bool            $string Enable loading from string
     *
     * @return Config
     */
    public static function load($values, $parser = null, $string = false)
    {
        return new static($values, $parser, $string);
    }

    /**
     * Loads a Config instance.
     *
     * @param  string|array    $values Filenames or string with configuration
     * @param  ?ParserInterface $parser Configuration parser
     * @param  bool            $string Enable loading from string
     */
    public function __construct($values, ?ParserInterface $parser = null, $string = false)
    {
        if ($string === true) {
            $this->loadFromString($values, $parser);
        } else {
            $this->loadFromFile($values, $parser);
        }

        parent::__construct($this->data);
    }

    /**
     * Loads configuration from file.
     *
     * @param  string|array     $path   Filenames or directories with configuration
     * @param  ?ParserInterface $parser Configuration parser
     *
     * @throws EmptyDirectoryException If `$path` is an empty directory
     */
    protected function loadFromFile($path, ?ParserInterface $parser = null)
    {
        $paths      = $this->getValidPath($path);
        $this->data = [];

        foreach ($paths as $path) {
            if ($parser === null) {
                // Get file information
                $info      = pathinfo($path);
                $parts     = explode('.', $info['basename']);
                $extension = array_pop($parts);

                // Skip the `dist` extension
                if ($extension === 'dist') {
                    $extension = array_pop($parts);
                }

                // Get file parser
                $parser = $this->getParser($extension);

                // Try to load file
                $this->data = array_replace_recursive($this->data, $parser->parseFile($path));

                // Clean parser
                $parser = null;
            } else {
                // Try to load file using specified parser
                $this->data = array_replace_recursive($this->data, $parser->parseFile($path));
            }
        }
    }

    /**
     * Writes configuration to file.
     *
     * @param  string           $filename   Filename to save configuration to
     * @param  ?WriterInterface $writer Configuration writer
     *
     * @throws WriteException if the data could not be written to the file
     */
    public function toFile($filename, ?WriterInterface $writer = null)
    {
        if ($writer === null) {
            // Get file information
            $info      = pathinfo($filename);
            $parts     = explode('.', $info['basename']);
            $extension = array_pop($parts);

            // Skip the `dist` extension
            if ($extension === 'dist') {
                $extension = array_pop($parts);
            }

            // Get file writer
            $writer = $this->getWriter($extension);

            // Try to save file
            $writer->toFile($this->all(), $filename);

            // Clean writer
            $writer = null;
        } else {
            // Try to load file using specified writer
            $writer->toFile($this->all(), $filename);
        }
    }

    /**
     * Loads configuration from string.
     *
     * @param string          $configuration String with configuration
     * @param ParserInterface $parser        Configuration parser
     */
    protected function loadFromString($configuration, ParserInterface $parser)
    {
        $this->data = [];

        // Try to parse string
        $this->data = array_replace_recursive($this->data, $parser->parseString($configuration));
    }

    /**
     * Writes configuration to string.
     *
     * @param  WriterInterface  $writer Configuration writer
     * @param boolean           $pretty Encode pretty
     */
    public function toString(WriterInterface $writer, $pretty = true)
    {
        return $writer->toString($this->all(), $pretty);
    }

    /**
     * Gets a parser for a given file extension.
     *
     * @param  string $extension
     *
     * @return ParserInterface
     *
     * @throws UnsupportedFormatException If `$extension` is an unsupported file format
     */
    protected function getParser($extension)
    {
        foreach ($this->supportedParsers as $parser) {
            if (in_array($extension, $parser::getSupportedExtensions())) {
                return new $parser();
            }
        }

        // If none exist, then throw an exception
        throw new UnsupportedFormatException('Unsupported configuration format');
    }

    /**
     * Gets a writer for a given file extension.
     *
     * @param  string $extension
     *
     * @return WriterInterface
     *
     * @throws UnsupportedFormatException If `$extension` is an unsupported file format
     */
    protected function getWriter($extension)
    {
        foreach ($this->supportedWriters as $writer) {
            if (in_array($extension, $writer::getSupportedExtensions())) {
                return new $writer();
            }
        }

        // If none exist, then throw an exception
        throw new UnsupportedFormatException('Unsupported configuration format'.$extension);
    }

    /**
     * Gets an array of paths
     *
     * @param  array $path
     *
     * @return array
     *
     * @throws FileNotFoundException|EmptyDirectoryException   If a file is not found at `$path`
     */
    protected function getPathFromArray($path)
    {
        $paths = [];

        foreach ($path as $unverifiedPath) {
            try {
                // Check if `$unverifiedPath` is optional
                // If it exists, then it's added to the list
                // If it doesn't, it throws an exception which we catch
                if ($unverifiedPath[0] !== '?') {
                    $paths = array_merge($paths, $this->getValidPath($unverifiedPath));
                    continue;
                }

                $optionalPath = ltrim($unverifiedPath, '?');
                $paths = array_merge($paths, $this->getValidPath($optionalPath));
            } catch (FileNotFoundException $e) {
                // If `$unverifiedPath` is optional, then skip it
                if ($unverifiedPath[0] === '?') {
                    continue;
                }

                // Otherwise rethrow the exception
                throw $e;
            }
        }

        return $paths;
    }

    /**
     * Checks `$path` to see if it is either an array, a directory, or a file.
     *
     * @param  string|array $path
     *
     * @return array
     *
     * @throws EmptyDirectoryException If `$path` is an empty directory
     *
     * @throws FileNotFoundException   If a file is not found at `$path`
     */
    protected function getValidPath($path)
    {
        // If `$path` is array
        if (is_array($path)) {
            return $this->getPathFromArray($path);
        }

        // If `$path` is a directory
        if (is_dir($path)) {
            $paths = glob($path . '/*.*');
            if (empty($paths)) {
                throw new EmptyDirectoryException("Configuration directory: [$path] is empty");
            }

            return $paths;
        }

        // If `$path` is not a file, throw an exception
        if (!file_exists($path)) {
            throw new FileNotFoundException("Configuration file: [$path] cannot be found");
        }

        return [$path];
    }
}
