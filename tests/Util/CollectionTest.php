<?php
declare(strict_types=1);

namespace Tests\Util;

use Arris\Util\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collection::class)]
class CollectionTest extends TestCase
{
    #[Test]
    public function constructorAndOfProduceSameResult(): void
    {
        $a = new Collection([1, 2, 3]);
        $b = Collection::of([1, 2, 3]);

        $this->assertEquals($a->all(), $b->all());
    }

    #[Test]
    public function allAndToArrayReturnItems(): void
    {
        $c = new Collection(['x', 'y']);
        $this->assertSame(['x', 'y'], $c->all());
        $this->assertSame(['x', 'y'], $c->toArray());
    }

    #[Test]
    public function constructorReindexesKeys(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2]);
        $this->assertSame([1, 2], $c->all());
    }

    #[Test]
    public function emptyCollection(): void
    {
        $c = new Collection();
        $this->assertTrue($c->isEmpty());
        $this->assertFalse($c->isNotEmpty());
        $this->assertCount(0, $c);
    }

    #[Test]
    public function collectionCount(): void
    {
        $c = new Collection([1, 2, 3]);
        $this->assertCount(3, $c);
    }

    #[Test]
    public function firstAndLast(): void
    {
        $c = new Collection([10, 20, 30]);
        $this->assertSame(10, $c->first());
        $this->assertSame(30, $c->last());
    }

    #[Test]
    public function firstAndLastOnEmpty(): void
    {
        $c = new Collection();
        $this->assertNull($c->first());
        $this->assertNull($c->last());
    }

    #[Test]
    public function getByIndex(): void
    {
        $c = new Collection(['a', 'b', 'c']);
        $this->assertSame('a', $c->get(0));
        $this->assertSame('c', $c->get(2));
        $this->assertNull($c->get(99));
    }

    #[Test]
    public function containsAndIndexOf(): void
    {
        $c = new Collection([1, 2, 3]);
        $this->assertTrue($c->contains(2));
        $this->assertFalse($c->contains(99));
        $this->assertSame(1, $c->indexOf(2));
        $this->assertFalse($c->indexOf(99));
    }

    #[Test]
    public function keys(): void
    {
        $c = new Collection(['a', 'b']);
        $this->assertSame([0, 1], $c->keys());
    }

    #[Test]
    public function map(): void
    {
        $c = new Collection([1, 2, 3]);
        $mapped = $c->map(fn($v) => $v * 2);
        $this->assertSame([2, 4, 6], $mapped->all());
        $this->assertNotSame($c, $mapped);
    }

    #[Test]
    public function filter(): void
    {
        $c = new Collection([0, 1, null, 2, '', 3]);
        $this->assertSame([1, 2, 3], $c->filter()->values()->all());

        $even = $c->filter(fn($v) => is_int($v) && $v % 2 === 0);
        $this->assertSame([0, 2], $even->all());
    }

    #[Test]
    public function reduce(): void
    {
        $c = new Collection([1, 2, 3, 4]);
        $sum = $c->reduce(fn($carry, $v) => $carry + $v, 0);
        $this->assertSame(10, $sum);
    }

    #[Test]
    public function eachReturnsSelf(): void
    {
        $c = new Collection([1, 2]);
        $result = [];
        $returned = $c->each(function ($v) use (&$result) { $result[] = $v * 2; });
        $this->assertSame([2, 4], $result);
        $this->assertSame($c, $returned);
    }

    #[Test]
    public function sort(): void
    {
        $c = new Collection([3, 1, 2]);
        $sorted = $c->sort(fn($a, $b) => $a <=> $b);
        $this->assertSame([1, 2, 3], $sorted->all());
    }

    #[Test]
    public function sortDesc(): void
    {
        $c = new Collection([1, 3, 2]);
        $this->assertSame([3, 2, 1], $c->sortDesc()->all());
    }

    #[Test]
    public function unique(): void
    {
        $c = new Collection([1, 2, 2, 3, 1]);
        $this->assertSame([1, 2, 3], $c->unique()->values()->all());
    }

    #[Test]
    public function reverse(): void
    {
        $c = new Collection([1, 2, 3]);
        $this->assertSame([3, 2, 1], $c->reverse()->all());
    }

    #[Test]
    public function slice(): void
    {
        $c = new Collection([1, 2, 3, 4, 5]);
        $this->assertSame([3, 4, 5], $c->slice(2)->all());
        $this->assertSame([2, 3], $c->slice(1, 2)->all());
    }

    #[Test]
    public function mergeWithArray(): void
    {
        $c = new Collection([1, 2]);
        $this->assertSame([1, 2, 3, 4], $c->merge([3, 4])->all());
    }

    #[Test]
    public function mergeWithCollection(): void
    {
        $c = new Collection([1, 2]);
        $other = new Collection([3, 4]);
        $this->assertSame([1, 2, 3, 4], $c->merge($other)->all());
    }

    #[Test]
    public function diff(): void
    {
        $c = new Collection([1, 2, 3, 4]);
        $this->assertSame([1, 4], $c->diff([2, 3])->values()->all());
    }

    #[Test]
    public function intersect(): void
    {
        $c = new Collection([1, 2, 3, 4]);
        $this->assertSame([2, 3], $c->intersect([2, 3, 5])->values()->all());
    }

    #[Test]
    public function pluck(): void
    {
        $c = new Collection([
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
        ]);
        $this->assertSame(['a', 'b'], $c->pluck('name'));
    }

    #[Test]
    public function groupBy(): void
    {
        $c = new Collection([
            ['role' => 'admin', 'name' => 'Alice'],
            ['role' => 'user', 'name' => 'Bob'],
            ['role' => 'admin', 'name' => 'Charlie'],
        ]);
        $grouped = $c->groupBy('role');
        $this->assertCount(2, $grouped['admin']);
        $this->assertCount(1, $grouped['user']);
    }

    #[Test]
    public function keyBy(): void
    {
        $c = new Collection([
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
        ]);
        $keyed = $c->keyBy('id');
        $this->assertSame('a', $keyed[1]['name'] ?? '');
        $this->assertSame('b', $keyed[2]['name'] ?? '');
    }

    #[Test]
    public function implode(): void
    {
        $c = new Collection(['a', 'b', 'c']);
        $this->assertSame('a,b,c', $c->implode());
        $this->assertSame('a|b|c', $c->implode('|'));
    }

    #[Test]
    public function chunk(): void
    {
        $c = new Collection([1, 2, 3, 4, 5]);
        $chunks = $c->chunk(2);
        $this->assertCount(3, $chunks);
        $this->assertSame([1, 2], $chunks[0]->all());
        $this->assertSame([5], $chunks[2]->all());
    }

    #[Test]
    public function random(): void
    {
        $c = new Collection([42]);
        $this->assertSame(42, $c->random());
    }

    #[Test]
    public function randomOnEmptyReturnsNull(): void
    {
        $c = new Collection();
        $this->assertNull($c->random());
    }

    #[Test]
    public function shuffleReturnsAllItems(): void
    {
        $c = new Collection([1, 2, 3, 4, 5]);
        $shuffled = $c->shuffle();
        $this->assertCount(5, $shuffled);
        $this->assertSame([], array_diff($c->all(), $shuffled->all()));
    }

    #[Test]
    public function add(): void
    {
        $c = new Collection([1]);
        $c->add(2);
        $this->assertSame([1, 2], $c->all());
    }

    #[Test]
    public function push(): void
    {
        $c = new Collection([1]);
        $c->push(2, 3);
        $this->assertSame([1, 2, 3], $c->all());
    }

    #[Test]
    public function pop(): void
    {
        $c = new Collection([1, 2, 3]);
        $this->assertSame(3, $c->pop());
        $this->assertSame([1, 2], $c->all());
    }

    #[Test]
    public function shift(): void
    {
        $c = new Collection([1, 2, 3]);
        $this->assertSame(1, $c->shift());
        $this->assertSame([2, 3], $c->all());
    }

    #[Test]
    public function unshift(): void
    {
        $c = new Collection([2, 3]);
        $c->unshift(0, 1);
        $this->assertSame([0, 1, 2, 3], $c->all());
    }

    #[Test]
    public function search(): void
    {
        $c = new Collection([1, 2, 3]);
        $result = $c->search(fn($v) => $v > 1);
        $this->assertSame(2, $result);

        $this->assertNull($c->search(fn($v) => $v > 99));
    }

    #[Test]
    public function every(): void
    {
        $c = new Collection([2, 4, 6]);
        $this->assertTrue($c->every(fn($v) => $v % 2 === 0));
        $this->assertFalse($c->every(fn($v) => $v > 3));
    }

    #[Test]
    public function some(): void
    {
        $c = new Collection([1, 3, 5]);
        $this->assertTrue($c->some(fn($v) => $v > 3));
        $this->assertFalse($c->some(fn($v) => $v > 10));
    }

    #[Test]
    public function tapReceivesSelf(): void
    {
        $c = new Collection([1, 2]);
        $captured = null;
        $returned = $c->tap(function ($col) use (&$captured) { $captured = $col; });
        $this->assertSame($c, $captured);
        $this->assertSame($c, $returned);
    }

    #[Test]
    public function pipeReturnsCallbackResult(): void
    {
        $c = new Collection([1, 2, 3]);
        $result = $c->pipe(fn($col) => $col->count());
        $this->assertSame(3, $result);
    }

    #[Test]
    public function valuesReindexes(): void
    {
        $c = new Collection([0 => 'a', 2 => 'b']);
        $this->assertSame([0 => 'a', 1 => 'b'], $c->values()->all());
    }

    #[Test]
    public function arrayAccess(): void
    {
        $c = new Collection([10, 20]);
        $this->assertTrue(isset($c[0]));
        $this->assertFalse(isset($c[99]));
        $this->assertSame(10, $c[0]);

        $c[] = 30;
        $this->assertSame(30, $c[2]);

        $c[1] = 99;
        $this->assertSame(99, $c[1]);

        unset($c[1]);
        $this->assertNull($c[1]);
    }

    #[Test]
    public function iterator(): void
    {
        $c = new Collection([1, 2, 3]);
        $result = [];
        foreach ($c as $v) {
            $result[] = $v;
        }
        $this->assertSame([1, 2, 3], $result);
    }

    #[Test]
    public function jsonSerialize(): void
    {
        $c = new Collection([1, 2, 3]);
        $this->assertSame([1, 2, 3], $c->jsonSerialize());
        $this->assertSame('[1,2,3]', json_encode($c));
    }
}
