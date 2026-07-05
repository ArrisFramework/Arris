<?php
declare(strict_types=1);

namespace Tests;

use Arris\App;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use PDO;

#[CoversClass(App::class)]
class AppTest extends TestCase
{
    protected function setUp(): void
    {
        // Сбрасываем реестр синглтонов перед каждым тестом
        App::reset();
    }

    #[Test]
    public function singletonReturnsSameInstance(): void
    {
        $instance1 = App::getInstance();
        $instance2 = App::getInstance();

        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(App::class, $instance1);
    }

    #[Test]
    public function lateStaticBindingCreatesIndependentInstances(): void
    {
        $base = App::getInstance();
        $heir = TestAppHeir::getInstance();

        // Это два РАЗНЫХ объекта
        $this->assertNotSame($base, $heir);
        $this->assertInstanceOf(TestAppHeir::class, $heir);

        // И у каждого СВОЙ конфиг со своими дефолтами
        $this->assertNull($base->getConfig('heir'));
        $this->assertTrue($heir->getConfig('heir'));
    }

    #[Test]
    public function factoryIsAliasForGetInstance(): void
    {
        $instance1 = App::factory();
        $instance2 = App::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    #[Test]
    public function repositorySetGetAndDefault(): void
    {
        $app = App::getInstance();

        $app->set('string_key', 'string_value');
        $app->set('array_key', ['nested' => 'value']);
        $app->add(['bulk' => 'add']);

        $this->assertEquals('string_value', $app->get('string_key'));
        $this->assertEquals(['nested' => 'value'], $app->get('array_key'));
        $this->assertEquals('add', $app->get('bulk'));
        $this->assertNull($app->get('missing'));
        $this->assertEquals('fallback', $app->get('missing', 'fallback'));
    }

    #[Test]
    public function magicPropertiesWorkWithOptionsRepo(): void
    {
        $app = App::getInstance();

        // Записываем через магический сеттер
        $app->magicProp = 'magic';

        // Читаем через магические методы
        $this->assertTrue(isset($app->magicProp));
        $this->assertEquals('magic', $app->magicProp);

        // Магический репозиторий — это тот же options
        $this->assertEquals('magic', $app->get('magicProp'));

        // И наоборот: set/get видно через магический геттер
        $app->set('regular', 'value');
        $this->assertEquals('value', $app->regular);
    }

    #[Test]
    public function invokeWorksAsGetterAndSetter(): void
    {
        $app = App::getInstance();

        // Как сеттер
        $app('invoke_key', 'invoke_val');

        // Как геттер (через основной репозиторий)
        $this->assertEquals('invoke_val', $app('invoke_key'));
        $this->assertEquals('invoke_val', $app->get('invoke_key'));
    }

    #[Test]
    public function serviceContainerBasicOperations(): void
    {
        $app = App::getInstance();
        $mockService = new \stdClass();
        $mockService->id = 42;

        $app->addService('test.service', $mockService);

        $this->assertTrue($app->isService('test.service'));
        $this->assertFalse($app->isService('nonexistent'));

        $retrieved = $app->getService('test.service');
        $this->assertSame($mockService, $retrieved);
        $this->assertEquals(42, $retrieved->id);
    }

    #[Test]
    public function serviceContainerWithClosure(): void
    {
        $app = App::getInstance();

        // Регистрируем сервис как замыкание (для ленивой инициализации)
        $app->addService('lazy.db', function() {
            return new \stdClass();
        });

        $service = $app->getService('lazy.db');
        $this->assertInstanceOf(\Closure::class, $service);

        // Вызываем замыкание
        $instance = $service();
        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    #[Test]
    public function getServiceTypeReturnsCorrectType(): void
    {
        $app = App::getInstance();

        $app->addService('obj', new \DateTime());
        $app->addService('arr', [1, 2, 3]);
        $app->addService('str', 'hello');

        $this->assertEquals('DateTime', $app->getServiceType('obj'));
        $this->assertEquals('array', $app->getServiceType('arr'));
        $this->assertEquals('string', $app->getServiceType('str'));
        $this->assertNull($app->getServiceType('missing'));
    }

    #[Test]
    public function configMethodsWork(): void
    {
        $app = App::getInstance();

        // setConfig / getConfig
        $app->setConfig('app.name', 'TestApp');
        $this->assertEquals('TestApp', $app->getConfig('app.name'));

        // addConfig (массив)
        $app->addConfig(['app' => ['version' => '1.0']]);
        $this->assertEquals('1.0', $app->getConfig('app.version'));

        // getConfig без ключа возвращает весь объект конфига
        $this->assertInstanceOf(\Arris\AppConfig::class, $app->getConfig());
    }

    #[Test]
    public function staticConfigAccessorDelegatesToInstance(): void
    {
        App::factory()->setConfig('static.key', 'static_value');

        $this->assertEquals('static_value', App::config('static.key'));
    }

    #[Test]
    public function optionsHasReturnsTrueForExistingKey(): void
    {
        $app = App::getInstance();
        $app->set('exists', 'value');

        $this->assertTrue($app->has('exists'));
        $this->assertFalse($app->has('missing'));
    }

    #[Test]
    public function optionsRemoveDeletesKey(): void
    {
        $app = App::getInstance();
        $app->set('temp', 'value');
        $this->assertTrue($app->has('temp'));

        $app->remove('temp');
        $this->assertFalse($app->has('temp'));
        $this->assertNull($app->get('temp'));
    }

    #[Test]
    public function optionsAllReturnsFullArray(): void
    {
        $app = App::getInstance();
        $app->set('a', 1)->set('b', 2);

        $this->assertEquals(['a' => 1, 'b' => 2], $app->all());
    }

    #[Test]
    public function optionsAddAndSetAreFluent(): void
    {
        $app = App::getInstance();

        $result = $app->add(['x' => 10])->set('y', 20);
        $this->assertSame($app, $result);
        $this->assertEquals(10, $app->get('x'));
        $this->assertEquals(20, $app->get('y'));
    }

    #[Test]
    public function hasConfigReturnsTrueForExistingKey(): void
    {
        $app = App::getInstance();
        $app->setConfig('test.key', 'value');

        $this->assertTrue($app->hasConfig('test.key'));
        $this->assertFalse($app->hasConfig('missing'));
    }

    #[Test]
    public function removeConfigSetsKeyToNull(): void
    {
        $app = App::getInstance();
        $app->setConfig('temp', 'value');
        $this->assertTrue($app->hasConfig('temp'));

        $app->removeConfig('temp');

        // remove делегирует в offsetUnset, который ставит null (а не удаляет ключ)
        $this->assertNull($app->getConfig('temp'));
    }

    #[Test]
    public function allConfigReturnsFullArray(): void
    {
        $app = App::getInstance();
        $app->setConfig('a', 1)->setConfig('b', 2);

        $this->assertSame(1, $app->allConfig()['a']);
        $this->assertSame(2, $app->allConfig()['b']);
    }

    #[Test]
    public function replaceConfigReplacesEntireConfig(): void
    {
        $app = App::getInstance();
        $app->setConfig('old', 'value');
        $app->replaceConfig(['new' => 'data']);

        $this->assertEquals('data', $app->getConfig('new'));
        $this->assertFalse($app->hasConfig('old'));
        $this->assertNull($app->getConfig('old'));
    }

    #[Test]
    public function addConfigWithDotObject(): void
    {
        $app = App::getInstance();
        $dot = new \Arris\Core\Dot(['key' => 'from_dot']);
        $app->addConfig($dot);

        $this->assertEquals('from_dot', $app->getConfig('key'));
    }

    #[Test]
    public function addConfigIsFluent(): void
    {
        $app = App::getInstance();

        $result = $app->addConfig(['a' => 1])->setConfig('b', 2);

        $this->assertSame($app, $result);
        $this->assertEquals(1, $app->getConfig('a'));
        $this->assertEquals(2, $app->getConfig('b'));
    }

    #[Test]
    public function preventCloningThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot clone');

        $app = App::getInstance();
        clone $app;
    }

    #[Test]
    public function preventSerializationThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot serialize');

        serialize(App::getInstance());
    }

    #[Test]
    public function resetClearsInstanceRegistry(): void
    {
        $instance1 = App::getInstance();
        App::reset();
        $instance2 = App::getInstance();

        // После reset() должен создаться новый объект
        $this->assertNotSame($instance1, $instance2);
    }
}

/**
 * Helper class for testing Late Static Binding
 */
class TestAppHeir extends App
{
    protected function getDefaultConfig(): array
    {
        return ['heir' => true];
    }
}