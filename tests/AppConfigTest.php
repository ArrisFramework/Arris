<?php
declare(strict_types=1);

namespace Tests;

use Arris\AppConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(AppConfig::class)]
class AppConfigTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/arris_test_' . bin2hex(random_bytes(8));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    }

    #[Test]
    public function arrayMergeRecursiveReplaceMergesCorrectly(): void
    {
        $original = [
            'a' => 1,
            'b' => ['c' => 2, 'd' => 3],
            'e' => ['f' => ['g' => 4]]
        ];

        $patch = [
            'b' => ['c' => 99, 'h' => 5],  // c перезаписан, h добавлен
            'e' => ['f' => ['i' => 6]],    // рекурсивное слияние
            'a' => null,                    // удаление ключа
            'j' => 7                        // новый ключ
        ];

        $reflection = new ReflectionClass(AppConfig::class);
        $method = $reflection->getMethod('arrayMergeRecursiveReplace');
        $method->setAccessible(true);

        $result = $method->invoke(null, $original, $patch);

        $expected = [
            'b' => ['c' => 99, 'd' => 3, 'h' => 5],
            'e' => ['f' => ['g' => 4, 'i' => 6]],
            'j' => 7
            // 'a' удален
        ];

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function constructorMergesDefaultsAndFileData(): void
    {
        // Создаем мок конфига
        $configFile = $this->tempDir . '/test.php';
        file_put_contents($configFile, '<?php return ["db" => ["host" => "prod", "user" => "admin"]];');

        $defaults = [
            'db' => ['host' => 'localhost', 'port' => 3306, 'user' => 'root'],
            'app' => ['debug' => true]
        ];

        $config = new AppConfig([$configFile], $defaults);

        // Файл перезаписал дефолты
        $this->assertEquals('prod', $config->get('db.host'));
        $this->assertEquals('admin', $config->get('db.user'));

        // Дефолты сохранились для отсутствующих ключей
        $this->assertEquals(3306, $config->get('db.port'));

        // Незатронутые ветки дефолтов
        $this->assertTrue($config->get('app.debug'));
    }

    #[Test]
    public function constructorHandlesEmptyFilesArray(): void
    {
        $defaults = ['key' => 'value'];
        $config = new AppConfig([], $defaults);

        $this->assertEquals('value', $config->get('key'));
    }

    #[Test]
    public function constructorHandlesMissingOptionalFiles(): void
    {
        // Префикс "?" должен игнорировать отсутствие файла
        // (если hassankhan/config поддерживает эту фичу)
        $defaults = ['fallback' => 'ok'];

        $config = new AppConfig(['?' . $this->tempDir . '/missing.php'], $defaults);

        $this->assertEquals('ok', $config->get('fallback'));
    }

    #[Test]
    public function eachInstanceHasIndependentData(): void
    {
        $config1 = new AppConfig([], ['app' => ['id' => 1]]);
        $config2 = new AppConfig([], ['app' => ['id' => 2]]);

        $this->assertEquals(1, $config1->get('app.id'));
        $this->assertEquals(2, $config2->get('app.id'));

        // Изменение одного не влияет на другой
        $config1->set('app.id', 999);
        $this->assertEquals(999, $config1->get('app.id'));
        $this->assertEquals(2, $config2->get('app.id')); // Не изменился!
    }
}