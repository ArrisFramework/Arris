<?php

declare(strict_types=1);

namespace Tests;

use Arris\Util\Timer;
use PHPUnit\Framework\TestCase;

class TimerTest extends TestCase
{
    protected function setUp(): void
    {
        Timer::$timers = [];
    }

    public function testInit(): void
    {
        Timer::init('test');

        $this->assertArrayHasKey('test', Timer::$timers);
        $this->assertEquals('test', Timer::$timers['test']['name']);
        $this->assertEquals(Timer::STATE_NEW, Timer::$timers['test']['state']);
        $this->assertEquals(0, Timer::$timers['test']['iterations']);
    }

    public function testInitDefaultName(): void
    {
        Timer::init();
        $this->assertArrayHasKey('default', Timer::$timers);

        Timer::init('');
        $this->assertArrayHasKey('default', Timer::$timers);

        Timer::init(null);
        $this->assertArrayHasKey('default', Timer::$timers);
    }

    public function testStart(): void
    {
        Timer::start('test', 4);

        $this->assertEquals(Timer::STATE_RUNNING, Timer::$timers['test']['state']);
        $this->assertEquals(4, Timer::$timers['test']['round']);
        $this->assertEquals(1, Timer::$timers['test']['iterations']);
    }

    public function testGo(): void
    {
        Timer::init('test');
        $result = Timer::go('test');

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0, $result);
        $this->assertEquals(Timer::STATE_RUNNING, Timer::$timers['test']['state']);
        $this->assertEquals(1, Timer::$timers['test']['iterations']);
    }

    public function testPause(): void
    {
        Timer::start('test');
        usleep(10000);

        $result = Timer::pause('test');

        $this->assertEquals(Timer::STATE_PAUSED, Timer::$timers['test']['state']);
        $this->assertIsString($result);
        $this->assertGreaterThan(0, (float)$result);
    }

    public function testStop(): void
    {
        Timer::start('test');
        usleep(10000);

        $result = Timer::stop('test');

        $this->assertEquals(Timer::STATE_STOPPED, Timer::$timers['test']['state']);
        $this->assertIsString($result);
        $this->assertGreaterThan(0, (float)$result);
    }

    public function testStopAll(): void
    {
        Timer::start('timer1');
        Timer::start('timer2');
        usleep(10000);

        Timer::stopAll();

        $this->assertEquals(Timer::STATE_STOPPED, Timer::$timers['timer1']['state']);
        $this->assertEquals(Timer::STATE_STOPPED, Timer::$timers['timer2']['state']);
        $this->assertGreaterThan(0, Timer::$timers['timer1']['time.total']);
        $this->assertGreaterThan(0, Timer::$timers['timer2']['time.total']);
    }

    public function testStopAllRemovesUnusedTimers(): void
    {
        Timer::init('unused');
        Timer::start('used');

        Timer::stopAll();

        $this->assertArrayNotHasKey('unused', Timer::$timers);
        $this->assertArrayHasKey('used', Timer::$timers);
    }

    public function testGet(): void
    {
        Timer::start('test');
        usleep(10000);
        Timer::stop('test');

        $result = Timer::get('test');

        $this->assertGreaterThan(0, $result);
    }

    public function testDestroy(): void
    {
        Timer::init('test');

        $this->assertTrue(Timer::destroy('test'));
        $this->assertArrayNotHasKey('test', Timer::$timers);
        $this->assertFalse(Timer::destroy('test'));
    }

    public function testIsExists(): void
    {
        $this->assertFalse(Timer::is_exists('test'));

        Timer::init('test');
        $this->assertTrue(Timer::is_exists('test'));
    }

    public function testGetState(): void
    {
        $this->assertEquals(Timer::STATE_UNDEFINED, Timer::get_state('ghost'));

        Timer::init('test');
        $this->assertEquals(Timer::STATE_NEW, Timer::get_state('test'));

        Timer::go('test');
        $this->assertEquals(Timer::STATE_RUNNING, Timer::get_state('test'));

        Timer::pause('test');
        $this->assertEquals(Timer::STATE_PAUSED, Timer::get_state('test'));

        Timer::go('test');
        Timer::stop('test');
        $this->assertEquals(Timer::STATE_STOPPED, Timer::get_state('test'));
    }

    public function testGetAllTimers(): void
    {
        Timer::start('first', 3);
        Timer::start('second', 4);
        Timer::stopAll();

        $result = Timer::get_all_timers();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('first', $result);
        $this->assertArrayHasKey('second', $result);
        $this->assertArrayHasKey('name', $result['first']);
        $this->assertArrayHasKey('time', $result['first']);
        $this->assertArrayHasKey('round', $result['first']);
    }

    public function testTimeAccumulation(): void
    {
        Timer::start('test');
        usleep(10000);
        Timer::pause('test');

        $paused = Timer::get('test');

        // Время во время паузы не должно увеличиваться
        usleep(10000);
        $this->assertEquals($paused, Timer::get('test'));

        // После возобновления должно увеличиться
        Timer::go('test');
        usleep(10000);
        Timer::stop('test');

        $this->assertGreaterThan($paused, Timer::get('test'));
    }

    public function testIterationsCount(): void
    {
        Timer::init('test');

        Timer::go('test');
        $this->assertEquals(1, Timer::$timers['test']['iterations']);
        Timer::pause('test');


        Timer::go('test');
        $this->assertEquals(2, Timer::$timers['test']['iterations']);
        Timer::pause('test');

        Timer::go('test');
        Timer::pause('test');
        $this->assertEquals(3, Timer::$timers['test']['iterations']);
    }

    public function testInstance(): void
    {
        $timer = new Timer();

        $this->assertEquals(Timer::STATE_NEW, $timer->timer['state']);

        $timer->go();

        $this->assertEquals(Timer::STATE_RUNNING, $timer->timer['state']);

        usleep(100000);

        $time = $timer->stop();

        $this->assertIsString($time);
        $this->assertGreaterThan(0, (float)$time);
        $this->assertEquals(Timer::STATE_STOPPED, $timer->get_state());

        $this->assertTrue($timer->is_exists());
        $this->assertTrue($timer->destroy());
    }

    public function testInstancePauseResume(): void
    {
        $timer = new Timer();
        $timer->go();
        usleep(10000);

        $pauseTime = (float)$timer->pause();
        $this->assertGreaterThan(0, $pauseTime);

        $timer->go();
        usleep(10000);
        $stopTime = (float)$timer->stop();

        $this->assertGreaterThan($pauseTime, $stopTime);
    }
}