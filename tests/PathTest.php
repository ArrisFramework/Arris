<?php

use PHPUnit\Framework\TestCase;
use Arris\Path;

require_once __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * @testdox Path class
 */
class PathTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        // self::markTestSkipped('This works');
    }
    /**
     * @return void
     * @testdox Create Path from string and join string
     */
    public function testSimpleCreatePath()
    {
        assertEquals(
            '/var/www/47news',
            Path::create('/var/www/')->join('47news')->toString()
        );
    }

    /**
     * @return void
     * @testdox Create Path with Path instance and join string
     */
    public function test2()
    {
        $p = Path::create('/var/www/')->join('47news');
        $this->assertEquals(
            '/var/www/47news/vendor',
            Path::create($p)->join('vendor')->toString()
        );
    }

    /**
     * @return void
     * @testdox Create Path from string, join Path instance
     */
    public function test3()
    {
        $this->assertEquals(
            '/var/www/frontend/images',
            Path::create('/var/www/')->join( Path::create('/frontend/images/') )->toString()
        );
    }

    /**
     * @return void
     * @testdox Path create from string, join Name created from Path (created by string + filename string)
     */
    public function test4()
    {
        $this->assertEquals(
            '/var/www/data/data.txt',
            Path::create('/var/www/')->joinName( Path::create('data')->joinName('data.txt') )
        );
    }

    /**
     * @return void
     * @testdox Path from Path instance, join subpath by Path instance, join name path path instance
     */
    public function test5()
    {
        $this->assertEquals(
            '/var/www/data/subdata/xxx/data/data.txt',
            Path::create(Path::create('/var/www/')->join('data') )
                ->join( Path::create('/subdata/xxx', true) )
                ->joinName( Path::create('data')->joinName('data.txt') )
                ->toString()
        );
    }

}