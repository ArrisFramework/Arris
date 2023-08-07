<?php

use Arris\Helpers\Arrays;

class HelpersArrayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @testdox Разбивает строку по $separator и возвращает массив значений, приведенных к INTEGER
     */
    public function explodeToIntTest()
    {
        $this->assertEquals([1, 2], Arrays::explodeToInt('1 2'));
    }

    /**
     * @return void
     * @testdox Разбивает строку по $separator и возвращает массив значений, приведенных к BOOL
     */
    public function explodeToBool()
    {
        $this->assertEquals([false, true, false], Arrays::explodeToType('0 1 0', ' ', 'bool'));
    }

    /**
     * @return void
     * @testdox Разбивает строку по $separator и возвращает массив значений, приведенных к array
     */
    public function explodeToArray()
    {
        $this->assertEquals([['A'], ['B'], ['C']], Arrays::explodeToType('A B C', ' ', 'array'));
    }

}