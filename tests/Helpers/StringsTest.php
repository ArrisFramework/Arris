<?php

declare(strict_types=1);

namespace Tests\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Arris\Helpers\Strings;

#[CoversClass(Strings::class)]
class StringsTest extends TestCase
{
    // ─────────────────────────────────────────────
    //  returnBytes()
    // ─────────────────────────────────────────────

    #[Test]
    #[TestDox('returnBytes: пустая строка возвращает 0')]
    public function returnBytesEmptyString(): void
    {
        $this->assertSame(0, Strings::returnBytes(''));
    }

    #[Test]
    #[TestDox('returnBytes: строка из пробелов возвращает 0')]
    public function returnBytesSpacesOnly(): void
    {
        $this->assertSame(0, Strings::returnBytes('   '));
    }

    #[Test]
    #[TestDox('returnBytes: некорректная строка возвращает 0')]
    public function returnBytesInvalidString(): void
    {
        $this->assertSame(0, Strings::returnBytes('abc'));
        $this->assertSame(0, Strings::returnBytes('10pb')); // 'p' не поддерживается
        $this->assertSame(0, Strings::returnBytes('-1k'));  // минус не поддерживается regex
    }

    #[Test]
    #[TestDox('returnBytes: число без суффикса')]
    public function returnBytesNoSuffix(): void
    {
        $this->assertSame(1024, Strings::returnBytes('1024'));
        $this->assertSame(1024, Strings::returnBytes(1024));
        $this->assertSame(1, Strings::returnBytes(1.5)); // (int) 1.5 = 1
    }

    #[Test]
    #[DataProvider('returnBytesSuffixProvider')]
    #[TestDox('returnBytes: суффиксы и регистр (#{"label"})')]
    public function returnBytesWithSuffixes(string|int|float $input, int $expected, string $label): void
    {
        $this->assertSame($expected, Strings::returnBytes($input));
    }

    public static function returnBytesSuffixProvider(): array
    {
        return [
            // Kilo
            ['1k', 1024, '1k'],
            ['1K', 1024, '1K'],
            ['1kb', 1024, '1kb'],
            ['1Kb', 1024, '1Kb'],
            ['1 k', 1024, '1 k (space)'],
            ['1 kb', 1024, '1 kb (space)'],
            [' 1 KB ', 1024, ' 1 KB (trim)'],

            // Mega
            ['1m', 1048576, '1m'],
            ['1M', 1048576, '1M'],
            ['1mb', 1048576, '1mb'],
            ['1Mb', 1048576, '1Mb'],

            // Giga
            ['1g', 1073741824, '1g'],
            ['1G', 1073741824, '1G'],
            ['1gb', 1073741824, '1gb'],
            ['1Gb', 1073741824, '1Gb'],

            // Tera
            ['1t', 1024 ** 4, '1t'],
            ['1T', 1024 ** 4, '1T'],
            ['1tb', 1024 ** 4, '1tb'],
            ['1Tb', 1024 ** 4, '1Tb'],

            // Fractional
            ['1.5g', (int) (1.5 * 1024 ** 3), '1.5g'],
            [' 1.5 Gb ', (int) (1.5 * 1024 ** 3), ' 1.5 Gb (spaces + fraction)'],
            ['0.5m', (int) (0.5 * 1024 ** 2), '0.5m'],
        ];
    }

    // ─────────────────────────────────────────────
    //  pluralForm()
    // ─────────────────────────────────────────────

    #[Test]
    #[TestDox('pluralForm: выбрасывает исключение при пустом массиве форм')]
    public function pluralFormThrowsOnEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Forms array cannot be empty.');

        Strings::pluralForm(1, []);
    }

    #[Test]
    #[DataProvider('pluralFormProvider')]
    #[TestDox('pluralForm: число #{"number"} → "#{"expected}" (#{"label"})')]
    public function pluralFormCorrectForms(
        int $number,
        array|string $forms,
        string $glue,
        string $expected,
        string $label
    ): void {
        $this->assertSame($expected, Strings::pluralForm($number, $forms, $glue));
    }

    public static function pluralFormProvider(): array
    {
        $stdForms = 'файл|файла|файлов';

        return [
            // Базовые случаи (1, 2, 5)
            [1, $stdForms, '|', 'файл', '1 файл'],
            [2, $stdForms, '|', 'файла', '2 файла'],
            [5, $stdForms, '|', 'файлов', '5 файлов'],

            // Исключения 11-14
            [11, $stdForms, '|', 'файлов', '11 файлов'],
            [12, $stdForms, '|', 'файлов', '12 файлов'],
            [13, $stdForms, '|', 'файлов', '13 файлов'],
            [14, $stdForms, '|', 'файлов', '14 файлов'],

            // Десятки и сотни
            [21, $stdForms, '|', 'файл', '21 файл'],
            [22, $stdForms, '|', 'файла', '22 файла'],
            [25, $stdForms, '|', 'файлов', '25 файлов'],
            [100, $stdForms, '|', 'файлов', '100 файлов'],
            [101, $stdForms, '|', 'файл', '101 файл'],
            [111, $stdForms, '|', 'файлов', '111 файлов'],
            [121, $stdForms, '|', 'файл', '121 файл'],
            [1000, $stdForms, '|', 'файлов', '1000 файлов'],
            [1001, $stdForms, '|', 'файл', '1001 файл'],

            // Ноль и отрицательные
            [0, $stdForms, '|', 'файлов', '0 файлов'],
            [-1, $stdForms, '|', 'файл', '-1 файл'],
            [-2, $stdForms, '|', 'файла', '-2 файла'],
            [-5, $stdForms, '|', 'файлов', '-5 файлов'],
            [-21, $stdForms, '|', 'файл', '-21 файл'],

            // Массив вместо строки
            [1, ['яблоко', 'яблока', 'яблок'], '|', 'яблоко', 'array: 1 яблоко'],
            [2, ['яблоко', 'яблока', 'яблок'], '|', 'яблока', 'array: 2 яблока'],

            // Другой разделитель
            [5, 'день|дня|дней', '|', 'дней', 'string glue: 5 дней'],
            [2, 'день,дня,дней', ',', 'дня', 'custom glue comma: 2 дня'],

            // 1 форма (дублируется)
            [1, 'файл', '|', 'файл', '1 form: 1'],
            [2, 'файл', '|', 'файл', '1 form: 2'],
            [5, 'файл', '|', 'файл', '1 form: 5'],

            // 2 формы (3-я = 2-я)
            [1, 'файл|файла', '|', 'файл', '2 forms: 1'],
            [2, 'файл|файла', '|', 'файла', '2 forms: 2'],
            [5, 'файл|файла', '|', 'файла', '2 forms: 5'],
        ];
    }
}