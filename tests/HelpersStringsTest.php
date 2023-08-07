<?php

use Arris\Helpers\Arrays;
use Arris\Helpers\Strings;

class HelpersStringsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @testdox Тест форм числительного
     */
    public function pluralFormTest()
    {
        $this->assertEquals('штука', Strings::pluralForm(1, ['штука', 'штуки', 'штук']));
        $this->assertEquals('штуки', Strings::pluralForm(2, ['штука', 'штуки', 'штук']));
        $this->assertEquals('штук', Strings::pluralForm(5, ['штука', 'штуки', 'штук']));
    }

    /**
     * @return void
     * @testdox Тест форм числительного, если форм 2
     */
    public function pluralFormTestFormsCount2()
    {
        $this->assertEquals('штука', Strings::pluralForm(1, ['штука', 'штуки']));
        $this->assertEquals('штуки', Strings::pluralForm(2, ['штука', 'штуки']));
        $this->assertEquals('штуки', Strings::pluralForm(5, ['штука', 'штуки']));
    }

    /**
     * @return void
     * @testdox Тест форм числительного, если форм 1
     */
    public function pluralFormTestFormsCount1()
    {
        $this->assertEquals('штука', Strings::pluralForm(1, ['штука']));
        $this->assertEquals('штука', Strings::pluralForm(2, ['штука']));
        $this->assertEquals('штука', Strings::pluralForm(5, ['штука']));
    }

    /**
     * @return void
     * @testdox Тест форм числительного, если массив форм пуст
     */
    public function pluralFormTestFormsEmpty()
    {
        $this->assertEquals('1', Strings::pluralForm(1, []));
        $this->assertEquals('2', Strings::pluralForm(2, []));
        $this->assertEquals('5', Strings::pluralForm(5, []));
    }

    /**
     * @return void
     * @testdox Тест форм числительного, если массив форм не массив
     */
    public function pluralFormTestFormsNotValid()
    {
        $this->assertEquals('1', Strings::pluralForm(1, false));
        $this->assertEquals('2', Strings::pluralForm(2, true));
        $this->assertEquals('5', Strings::pluralForm(5, new stdClass()));
    }

}