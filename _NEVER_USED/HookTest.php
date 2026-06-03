<?php

use Arris\Hook;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

class HookTest extends TestCase
{
    public static function setUpBeforeClass():void
    {
        Hook::init();

        // где-то там в плагинах на хуки вешаем методы
        Hook::register('post:add:comment', function (){
            return 'Event post:add:comment ';
        });

        Hook::register('post:add:topic', function (){
            return 'Event post:add:topic';
        });

        Hook::register('example', function (){
            return "Example: 100;" ;
        }, 100);

        Hook::register('example', function (){
            return "Example: 90;";
        }, 90);

        echo Hook::run('post:add:topic');
    }

    /**
     * @return void
     * @testdox Test simple hook to event
     */
    public function test1()
    {
        $x = Hook::run('post:add:topic');
        $this->assertEquals('Event post:add:topic', Hook::run('post:add:topic'));
    }

    /**
     * @return void
     * @testdox Run non-exists hook
     */
    public function test2()
    {
        $this->assertFalse( Hook::run('note'));
    }

    /**
     * @return void
     * @testdox Run event priority 90, after that priority 100
     */
    public function test3()
    {
        $this->assertEquals('Example: 90;Example: 100;', Hook::run('example'));
    }

}