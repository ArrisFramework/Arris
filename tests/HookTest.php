<?php

use Arris\Hook;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

class HookTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        self::markTestSkipped('Not supported before PHPUnit 8+');

        Hook::init();

// где-то там в плагинах на хуки вешаем методы
        Hook::register('post:add:comment', function (){
            echo 'Event post:add:comment ';
        });

        Hook::register('post:add:topic', function (){
            echo 'Event post:add:topic';
        });

        Hook::register('example', function (){
            echo "Example: 100;";
        }, 100);

        Hook::register('example', function (){
            echo "Example: 90;";
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
        $this->assertContains('Event post:add:topic', Hook::run('post:add:topic'));
    }

    /**
     * @return void
     * @testdox Run non-exists hook
     */
    public function test2()
    {
        $this->assertContains('', Hook::run('note'));
    }

    /**
     * @return void
     * @testdox Run event priority 90, after that priority 100
     */
    public function test3()
    {
        $this->assertContains('Example: 90;Example: 100;', Hook::run('example'));
    }

}