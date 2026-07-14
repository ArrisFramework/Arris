<?php

declare(strict_types=1);

namespace Tests\Util;

use Arris\Util\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
class RequestTest extends TestCase
{
    // ── string() / str() ──────────────────────────────────────────────

    #[Test]
    public function stringReturnsValueFromSource(): void
    {
        $this->assertSame('hello', Request::string('name', from: ['name' => 'hello']));
    }

    #[Test]
    public function stringReturnsDefaultWhenFieldMissing(): void
    {
        $this->assertSame('fallback', Request::string('missing', default: 'fallback', from: []));
    }

    #[Test]
    public function stringCastsNonStringValueToString(): void
    {
        $this->assertSame('42', Request::string('val', from: ['val' => 42]));
        $this->assertSame('1', Request::string('val', from: ['val' => true]));
    }

    #[Test]
    public function stringTrimsByDefault(): void
    {
        $this->assertSame('hello', Request::string('name', from: ['name' => '  hello  ']));
    }

    #[Test]
    public function stringNoTrim(): void
    {
        $this->assertSame('  hello  ', Request::string('name', trim: false, from: ['name' => '  hello  ']));
    }

    #[Test]
    public function stringRespectsMaxLength(): void
    {
        $this->assertSame('hel', Request::string('name', maxLength: 3, from: ['name' => 'hello']));
    }

    #[Test]
    public function stringEmptyNotAllowedReturnsDefault(): void
    {
        $this->assertSame('fallback', Request::string('name', allowEmpty: false, default: 'fallback', from: ['name' => '']));
    }

    #[Test]
    public function stringEmptyAllowedReturnsEmpty(): void
    {
        $this->assertSame('', Request::string('name', allowEmpty: true, from: ['name' => '']));
    }

    #[Test]
    public function strDelegatesToString(): void
    {
        $this->assertSame(
            Request::string('name', from: ['name' => 'test']),
            Request::str('name', from: ['name' => 'test'])
        );
    }

    // ── email() ───────────────────────────────────────────────────────

    #[Test]
    public function emailValid(): void
    {
        $this->assertSame('user@example.com', Request::email('e', from: ['e' => 'user@example.com']));
    }

    #[Test]
    public function emailInvalidReturnsDefault(): void
    {
        $this->assertSame('fallback', Request::email('e', default: 'fallback', from: ['e' => 'not-an-email']));
    }

    #[Test]
    public function emailEmptyReturnsDefault(): void
    {
        $this->assertSame('', Request::email('e', from: ['e' => '']));
    }

    #[Test]
    public function emailMissingReturnsDefault(): void
    {
        $this->assertSame('fallback', Request::email('e', default: 'fallback', from: []));
    }

    #[Test]
    public function emailMaxLength254(): void
    {
        $longLocal = str_repeat('a', 64);
        $email = "{$longLocal}@example.com";
        $this->assertSame($email, Request::email('e', from: ['e' => $email]));
    }

    // ── int() ─────────────────────────────────────────────────────────

    #[Test]
    public function intReturnsValue(): void
    {
        $this->assertSame(42, Request::int('n', from: ['n' => 42]));
    }

    #[Test]
    public function intCastsString(): void
    {
        $this->assertSame(7, Request::int('n', from: ['n' => '7']));
    }

    #[Test]
    public function intReturnsDefaultForInvalid(): void
    {
        $this->assertSame(0, Request::int('n', from: ['n' => 'abc']));
    }

    #[Test]
    public function intReturnsDefaultForFloat(): void
    {
        $this->assertSame(5, Request::int('n', default: 5, from: ['n' => '3.14']));
    }

    #[Test]
    public function intRespectsMin(): void
    {
        $this->assertSame(0, Request::int('n', min: 10, from: ['n' => 5]));
    }

    #[Test]
    public function intRespectsMax(): void
    {
        $this->assertSame(0, Request::int('n', max: 10, from: ['n' => 15]));
    }

    #[Test]
    public function intWithinRange(): void
    {
        $this->assertSame(7, Request::int('n', min: 0, max: 10, from: ['n' => 7]));
    }

    #[Test]
    public function intZeroIsValid(): void
    {
        $this->assertSame(0, Request::int('n', from: ['n' => 0]));
    }

    #[Test]
    public function intNegativeValid(): void
    {
        $this->assertSame(-5, Request::int('n', from: ['n' => -5]));
    }

    // ── bool() ────────────────────────────────────────────────────────

    #[Test]
    public function boolTrueString(): void
    {
        $this->assertTrue(Request::bool('b', from: ['b' => 'true']));
    }

    #[Test]
    public function boolOneString(): void
    {
        $this->assertTrue(Request::bool('b', from: ['b' => '1']));
    }

    #[Test]
    public function boolOnString(): void
    {
        $this->assertTrue(Request::bool('b', from: ['b' => 'on']));
    }

    #[Test]
    public function boolFalseString(): void
    {
        $this->assertFalse(Request::bool('b', from: ['b' => 'false']));
    }

    #[Test]
    public function boolZeroString(): void
    {
        $this->assertFalse(Request::bool('b', from: ['b' => '0']));
    }

    #[Test]
    public function boolOffString(): void
    {
        $this->assertFalse(Request::bool('b', from: ['b' => 'off']));
    }

    #[Test]
    public function boolEmptyString(): void
    {
        $this->assertFalse(Request::bool('b', from: ['b' => '']));
    }

    #[Test]
    public function boolActualBool(): void
    {
        $this->assertTrue(Request::bool('b', from: ['b' => true]));
        $this->assertFalse(Request::bool('b', from: ['b' => false]));
    }

    #[Test]
    public function boolNumericValue(): void
    {
        $this->assertTrue(Request::bool('b', from: ['b' => 42]));
        $this->assertFalse(Request::bool('b', from: ['b' => 0]));
    }

    #[Test]
    public function boolReturnsDefaultWhenMissing(): void
    {
        $this->assertTrue(Request::bool('b', default: true, from: []));
    }

    #[Test]
    public function boolCaseInsensitive(): void
    {
        $this->assertTrue(Request::bool('b', from: ['b' => 'TRUE']));
        $this->assertTrue(Request::bool('b', from: ['b' => 'True']));
        $this->assertFalse(Request::bool('b', from: ['b' => 'FALSE']));
    }

    // ── checkbox() ────────────────────────────────────────────────────

    #[Test]
    public function checkboxReturnsBool(): void
    {
        $result = Request::checkbox('c', from: ['c' => '1']);
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    #[Test]
    public function checkboxFalse(): void
    {
        $result = Request::checkbox('c', from: ['c' => '0']);
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    // ── array() ───────────────────────────────────────────────────────

    #[Test]
    public function arrayReturnsValues(): void
    {
        $this->assertSame(['a', 'b', 'c'], Request::array('items', from: ['items' => ['a', 'b', 'c']]));
    }

    #[Test]
    public function arrayReturnsDefaultWhenMissing(): void
    {
        $this->assertSame(['x'], Request::array('items', default: ['x'], from: []));
    }

    #[Test]
    public function arrayReturnsDefaultWhenNotArray(): void
    {
        $this->assertSame([], Request::array('items', from: ['items' => 'not-array']));
    }

    #[Test]
    public function arrayCastsElementsToString(): void
    {
        $this->assertSame(['1', '2', '3'], Request::array('items', from: ['items' => [1, 2, 3]]));
    }

    #[Test]
    public function arrayRespectsMaxLength(): void
    {
        $this->assertSame(['he', 'wo'], Request::array('items', maxLength: 2, from: ['items' => ['hello', 'world']]));
    }

    // ── arr() ─────────────────────────────────────────────────────────

    #[Test]
    public function arrSimpleArray(): void
    {
        $this->assertSame(['a', 'b'], Request::arr('items', from: ['items' => ['a', 'b']]));
    }

    #[Test]
    public function arrNestedArray(): void
    {
        $data = ['nominations' => ['ids' => ['1', '2'], 'titles' => ['A', 'B']]];
        $result = Request::arr('nominations', from: $data);
        $this->assertSame(['ids' => ['1', '2'], 'titles' => ['A', 'B']], $result);
    }

    #[Test]
    public function arrDeepNestedArray(): void
    {
        $data = ['level1' => ['level2' => ['value']]];
        $result = Request::arr('level1', from: $data);
        $this->assertSame(['level2' => ['value']], $result);
    }

    #[Test]
    public function arrWithTransposeMatrix(): void
    {
        $data = ['items' => ['ids' => ['1', '2'], 'titles' => ['A', 'B']]];
        $result = Request::arr('items', transposeMatrix: true, from: $data);
        $this->assertSame([
            ['ids' => '1', 'titles' => 'A'],
            ['ids' => '2', 'titles' => 'B'],
        ], $result);
    }

    #[Test]
    public function arrReturnsDefaultWhenMissing(): void
    {
        $this->assertSame(['fallback'], Request::arr('items', default: ['fallback'], from: []));
    }

    #[Test]
    public function arrReturnsDefaultWhenNotArray(): void
    {
        $this->assertSame([], Request::arr('items', from: ['items' => 'string']));
    }

    #[Test]
    public function arrRespectsMaxLength(): void
    {
        $data = ['items' => ['long' => 'hello']];
        $result = Request::arr('items', maxLength: 3, from: $data);
        $this->assertSame(['long' => 'hel'], $result);
    }

    // ── url() ─────────────────────────────────────────────────────────

    #[Test]
    public function urlValid(): void
    {
        $this->assertSame('https://example.com', Request::url('u', from: ['u' => 'https://example.com']));
    }

    #[Test]
    public function urlInvalidReturnsDefault(): void
    {
        $this->assertSame('fallback', Request::url('u', default: 'fallback', from: ['u' => 'not a url']));
    }

    #[Test]
    public function urlEmptyReturnsDefault(): void
    {
        $this->assertSame('', Request::url('u', from: ['u' => '']));
    }

    #[Test]
    public function urlMissingReturnsDefault(): void
    {
        $this->assertSame('fallback', Request::url('u', default: 'fallback', from: []));
    }

    // ── text() ────────────────────────────────────────────────────────

    #[Test]
    public function textStripsHtmlByDefault(): void
    {
        $this->assertSame('hello', Request::text('t', from: ['t' => '<b>hello</b>']));
    }

    #[Test]
    public function textEscapesHtml(): void
    {
        $this->assertSame('', Request::text('t', from: ['t' => '<script>']));
    }

    #[Test]
    public function textAllowHtml(): void
    {
        $this->assertSame('<b>hello</b>', Request::text('t', allowHtml: true, from: ['t' => '<b>hello</b>']));
    }

    #[Test]
    public function textRemovesEmptyBr(): void
    {
        $this->assertSame('hello', Request::text('t', allowHtml: true, from: ['t' => 'hello<br>']));
    }

    #[Test]
    public function textRemovesEmptyP(): void
    {
        $this->assertSame('<p>hello</p>', Request::text('t', allowHtml: true, from: ['t' => '<p>hello</p><p></p>']));
    }

    #[Test]
    public function textRemovesPWithNbsp(): void
    {
        $this->assertSame('<p>hello</p>', Request::text('t', allowHtml: true, from: ['t' => '<p>hello</p><p>&nbsp;</p>']));
    }

    #[Test]
    public function textNoEmptyContentFalse(): void
    {
        $result = Request::text('t', allowHtml: true, noEmptyContent: false, from: ['t' => '<p></p>']);
        $this->assertSame('<p></p>', $result);
    }

    #[Test]
    public function textReturnsEmptyWhenOnlyWhitespace(): void
    {
        $this->assertSame('', Request::text('t', from: ['t' => '   ']));
    }

    #[Test]
    public function textNormalizesWhitespace(): void
    {
        $this->assertSame('hello world', Request::text('t', from: ['t' => "hello   world"]));
    }

    // ── transposeMatrix() ─────────────────────────────────────────────

    #[Test]
    public function transposeMatrixEmpty(): void
    {
        $this->assertSame([], Request::transposeMatrix([]));
    }

    #[Test]
    public function transposeMatrixSingleRow(): void
    {
        $data = ['ids' => ['1', '2', '3'], 'titles' => ['A', 'B', 'C']];
        $expected = [
            ['ids' => '1', 'titles' => 'A'],
            ['ids' => '2', 'titles' => 'B'],
            ['ids' => '3', 'titles' => 'C'],
        ];
        $this->assertSame($expected, Request::transposeMatrix($data));
    }

    #[Test]
    public function transposeMatrixUnevenLength(): void
    {
        $data = ['ids' => ['1', '2'], 'titles' => ['A']];
        $expected = [
            ['ids' => '1', 'titles' => 'A'],
            ['ids' => '2', 'titles' => null],
        ];
        $this->assertSame($expected, Request::transposeMatrix($data));
    }

    #[Test]
    public function transposeMatrixSingleColumn(): void
    {
        $data = ['name' => ['Alice']];
        $expected = [['name' => 'Alice']];
        $this->assertSame($expected, Request::transposeMatrix($data));
    }
}
