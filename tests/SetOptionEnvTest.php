<?php

use PHPUnit\Framework\TestCase;
use function Arris\setOptionEnv as test;

require_once __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * @testdox setOptionsEnv function
 */
class SetOptionEnvTest extends TestCase
{
    public array $options = [
        'key1'  =>  'key1 is 1',
        'key2'  =>  'key2 is 4'
    ];

    public static function setUpBeforeClass():void
    {
        putenv("FOO=BAR");
        putenv('NGINX.CACHE_KEY_FORMAT=GET|||HOST|PATH');
    }

    /**
     * @return void
     * @testdox Present key
     */
    public function testOptionHaveField()
    {
        $this->assertEquals('key1 is 1', test($this->options, 'key1', 'FOO'));
        $this->assertEquals('BAR', test(['x' => 'y'], 'xz', 'FOO', 40));
    }

    /**
     * @return void
     * @testdox option key null or not exist, use env
     */
    public function test2()
    {
        $this->assertEquals(null, test($this->options, 'key3', null));
        $this->assertEquals('BAR', test($this->options, 'key3', 'FOO'));
        $this->assertEquals(40, test($this->options, 'key3', 'XXX', 40));

        $this->assertEquals('BAR', test($this->options, null, 'FOO'));
        $this->assertEquals('ZZZ', test($this->options, null, 'key3', 'ZZZ'));
        $this->assertEquals('ZZZ', test($this->options, null, null, 'ZZZ'));

        $this->assertEquals('GET|||HOST|PATH', test([ '__cache_key_format' => '213'], 'cache_key_format', 'NGINX.CACHE_KEY_FORMAT', 'GET'));
    }

    /**
     * @return void
     * @testdox empty options
     */
    public function test3()
    {
        $this->assertEquals('BAR', test([], 'key3', 'FOO'));
        $this->assertEquals('ZZZ', test([], 'key3', 'XXX', 'ZZZ'));
        $this->assertEquals('ZZZ', test([], 'key3', null, 'ZZZ'));
        $this->assertEquals('BAR', test([], null, 'FOO', 'ZZZ'));
        $this->assertEquals('ZZZ', test([], null, 'XXX', 'ZZZ'));
        $this->assertEquals('ZZZ', test([], null, 'XXX', 'ZZZ'));
    }


}