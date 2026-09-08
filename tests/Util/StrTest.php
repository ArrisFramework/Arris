<?php
declare(strict_types=1);

namespace Tests\Util;

use Arris\Util\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Str::class)]
class StrTest extends TestCase
{
    #[Test]
    public function constructorAndOf(): void
    {
        $a = new Str('hello');
        $b = Str::of('hello');
        $this->assertSame('hello', (string)$a);
        $this->assertSame('hello', (string)$b);
    }

    #[Test]
    public function toStringMethod(): void
    {
        $s = new Str('test');
        $this->assertSame('test', $s->toString());
        $this->assertSame('test', (string)$s);
    }

    #[Test]
    public function length(): void
    {
        $s = new Str('hello');
        $this->assertSame(5, $s->length());
    }

    #[Test]
    public function lengthMultibyte(): void
    {
        $s = new Str('привет');
        $this->assertSame(6, $s->length());
    }

    #[Test]
    public function lower(): void
    {
        $s = Str::of('HELLO')->lower();
        $this->assertSame('hello', (string)$s);
    }

    #[Test]
    public function upper(): void
    {
        $s = Str::of('hello')->upper();
        $this->assertSame('HELLO', (string)$s);
    }

    #[Test]
    public function ucfirst(): void
    {
        $s = Str::of('hello')->ucfirst();
        $this->assertSame('Hello', (string)$s);
    }

    #[Test]
    public function lcfirst(): void
    {
        $s = Str::of('HELLO')->lcfirst();
        $this->assertSame('hELLO', (string)$s);
    }

    #[Test]
    public function substr(): void
    {
        $s = Str::of('hello world')->substr(0, 5);
        $this->assertSame('hello', (string)$s);
    }

    #[Test]
    public function replace(): void
    {
        $s = Str::of('hello world')->replace('world', 'there');
        $this->assertSame('hello there', (string)$s);
    }

    #[Test]
    public function replaceRegex(): void
    {
        $s = Str::of('abc123xyz')->replaceRegex('/\d+/', '[digits]');
        $this->assertSame('abc[digits]xyz', (string)$s);
    }

    #[Test]
    public function trim(): void
    {
        $s = Str::of('  hello  ')->trim();
        $this->assertSame('hello', (string)$s);
    }

    #[Test]
    public function trimLeft(): void
    {
        $s = Str::of('  hello  ')->trimLeft();
        $this->assertSame('hello  ', (string)$s);
    }

    #[Test]
    public function trimRight(): void
    {
        $s = Str::of('  hello  ')->trimRight();
        $this->assertSame('  hello', (string)$s);
    }

    #[Test]
    public function contains(): void
    {
        $s = new Str('hello world');
        $this->assertTrue($s->contains('world'));
        $this->assertFalse($s->contains('xyz'));
    }

    #[Test]
    public function containsAll(): void
    {
        $s = new Str('hello world');
        $this->assertTrue($s->containsAll(['hello', 'world']));
        $this->assertFalse($s->containsAll(['hello', 'mars']));
    }

    #[Test]
    public function containsAny(): void
    {
        $s = new Str('hello world');
        $this->assertTrue($s->containsAny(['hello', 'mars']));
        $this->assertFalse($s->containsAny(['mars', 'venus']));
    }

    #[Test]
    public function startsWith(): void
    {
        $s = new Str('hello world');
        $this->assertTrue($s->startsWith('hello'));
        $this->assertFalse($s->startsWith('world'));
    }

    #[Test]
    public function endsWith(): void
    {
        $s = new Str('hello world');
        $this->assertTrue($s->endsWith('world'));
        $this->assertFalse($s->endsWith('hello'));
    }

    #[Test]
    public function after(): void
    {
        $s = Str::of('hello@world')->after('@');
        $this->assertSame('world', (string)$s);
    }

    #[Test]
    public function afterLast(): void
    {
        $s = Str::of('a@b@c')->afterLast('@');
        $this->assertSame('c', (string)$s);
    }

    #[Test]
    public function before(): void
    {
        $s = Str::of('hello@world')->before('@');
        $this->assertSame('hello', (string)$s);
    }

    #[Test]
    public function beforeLast(): void
    {
        $s = Str::of('a@b@c')->beforeLast('@');
        $this->assertSame('a@b', (string)$s);
    }

    #[Test]
    public function afterWithMissingDelimiter(): void
    {
        $s = Str::of('hello')->after('@');
        $this->assertSame('hello', (string)$s);
    }

    #[Test]
    public function match(): void
    {
        $s = new Str('abc123def');
        $this->assertSame('123', $s->match('/\d+/'));
        $this->assertNull($s->match('/[x-z]+/'));
    }

    #[Test]
    public function matchAll(): void
    {
        $s = new Str('a1 b2 c3');
        $this->assertSame(['1', '2', '3'], $s->matchAll('/\d/'));
    }

    #[Test]
    public function padLeft(): void
    {
        $s = Str::of('hello')->padLeft(8, '-');
        $this->assertSame('---hello', (string)$s);
    }

    #[Test]
    public function padRight(): void
    {
        $s = Str::of('hello')->padRight(8, '-');
        $this->assertSame('hello---', (string)$s);
    }

    #[Test]
    public function padBoth(): void
    {
        $s = Str::of('hello')->padBoth(9, '-');
        $this->assertSame('--hello--', (string)$s);
    }

    #[Test]
    public function repeat(): void
    {
        $s = Str::of('ab')->repeat(3);
        $this->assertSame('ababab', (string)$s);
    }

    #[Test]
    public function reverse(): void
    {
        $s = Str::of('hello')->reverse();
        $this->assertSame('olleh', (string)$s);
    }

    #[Test]
    public function slug(): void
    {
        $s = Str::of('Hello World Foo')->slug();
        $this->assertSame('hello-world-foo', (string)$s);
    }

    #[Test]
    public function limit(): void
    {
        $s = Str::of('hello world')->limit(5);
        $this->assertSame('hello...', (string)$s);
    }

    #[Test]
    public function limitShortString(): void
    {
        $s = Str::of('hi')->limit(5);
        $this->assertSame('hi', (string)$s);
    }

    #[Test]
    public function words(): void
    {
        $s = Str::of('one two three four')->words(2);
        $this->assertSame('one two...', (string)$s);
    }

    #[Test]
    public function isEmptyString(): void
    {
        $this->assertTrue((new Str(''))->isEmpty());
        $this->assertFalse((new Str('x'))->isEmpty());
    }

    #[Test]
    public function isBlankString(): void
    {
        $this->assertTrue((new Str('  '))->isBlank());
        $this->assertFalse((new Str('x'))->isBlank());
    }

    #[Test]
    public function append(): void
    {
        $s = Str::of('hello')->append(' world');
        $this->assertSame('hello world', (string)$s);
    }

    #[Test]
    public function prepend(): void
    {
        $s = Str::of('world')->prepend('hello ');
        $this->assertSame('hello world', (string)$s);
    }

    #[Test]
    public function explode(): void
    {
        $s = new Str('a,b,c');
        $this->assertSame(['a,b,c'], $s->explode());    // default separator is space
        $this->assertSame(['a', 'b', 'c'], $s->explode(','));
    }

    #[Test]
    public function chaining(): void
    {
        $s = Str::of('  HELLO WORLD  ')
            ->trim()
            ->lower()
            ->ucfirst()
            ->substr(0, 5);
        $this->assertSame('Hello', (string)$s);
    }

    #[Test]
    public function jsonSerialize(): void
    {
        $s = new Str('test');
        $this->assertSame('"test"', json_encode($s));
    }

    // ── Immutability contract ─────────────────────────────────────────

    #[Test]
    #[DataProvider('immutableTransformers')]
    public function transformerReturnsNewInstanceAndKeepsOriginal(string $seed, callable $transform, string $expected): void
    {
        $original = Str::of($seed);
        $result = $transform($original);

        $this->assertSame($seed, (string)$original, 'Original must not be mutated');
        $this->assertSame($expected, (string)$result);
        $this->assertNotSame($original, $result, 'Transformer must return a new instance');
    }

    public static function immutableTransformers(): iterable
    {
        yield 'lower'      => ['HELLO',       fn(Str $s) => $s->lower(),                 'hello'];
        yield 'upper'      => ['hello',       fn(Str $s) => $s->upper(),                 'HELLO'];
        yield 'ucfirst'    => ['hello',       fn(Str $s) => $s->ucfirst(),               'Hello'];
        yield 'lcfirst'    => ['HELLO',       fn(Str $s) => $s->lcfirst(),               'hELLO'];
        yield 'substr'     => ['hello world', fn(Str $s) => $s->substr(0, 5),            'hello'];
        yield 'replace'    => ['hello world', fn(Str $s) => $s->replace('world', 'there'), 'hello there'];
        yield 'replaceRegex' => ['abc123',    fn(Str $s) => $s->replaceRegex('/\d+/', 'X'), 'abcX'];
        yield 'trim'       => ['  hi  ',      fn(Str $s) => $s->trim(),                  'hi'];
        yield 'trimLeft'   => ['  hi  ',      fn(Str $s) => $s->trimLeft(),              'hi  '];
        yield 'trimRight'  => ['  hi  ',      fn(Str $s) => $s->trimRight(),             '  hi'];
        yield 'after'      => ['hello@world', fn(Str $s) => $s->after('@'),              'world'];
        yield 'afterLast'  => ['a@b@c',       fn(Str $s) => $s->afterLast('@'),          'c'];
        yield 'before'     => ['hello@world', fn(Str $s) => $s->before('@'),             'hello'];
        yield 'beforeLast' => ['a@b@c',       fn(Str $s) => $s->beforeLast('@'),         'a@b'];
        yield 'padLeft'    => ['hi',          fn(Str $s) => $s->padLeft(4, '-'),         '--hi'];
        yield 'padRight'   => ['hi',          fn(Str $s) => $s->padRight(4, '-'),        'hi--'];
        yield 'padBoth'    => ['hi',          fn(Str $s) => $s->padBoth(5, '-'),         '-hi--'];
        yield 'repeat'     => ['ab',          fn(Str $s) => $s->repeat(2),               'abab'];
        yield 'reverse'    => ['hello',       fn(Str $s) => $s->reverse(),               'olleh'];
        yield 'slug'       => ['Hello World', fn(Str $s) => $s->slug(),                  'hello-world'];
        yield 'append'     => ['hello',       fn(Str $s) => $s->append('!'),             'hello!'];
        yield 'prepend'    => ['world',       fn(Str $s) => $s->prepend('!'),            '!world'];
    }

    #[Test]
    public function shuffleDoesNotMutateOriginal(): void
    {
        $original = Str::of('hello');
        $result = $original->shuffle();

        $this->assertSame('hello', (string)$original);
        $this->assertNotSame($original, $result);
    }

    #[Test]
    public function limitReturnsSameInstanceWhenStringFits(): void
    {
        $s = Str::of('hi');
        $this->assertSame($s, $s->limit(5));
    }

    #[Test]
    public function wordsReturnsSameInstanceWhenStringFits(): void
    {
        $s = Str::of('one two');
        $this->assertSame($s, $s->words(5));
    }
}
