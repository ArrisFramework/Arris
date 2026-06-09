<?php

namespace Tests\Config\Parser;

use Arris\Core\Config\Parser\Properties;
use PHPUnit\Framework\TestCase;

class PropertiesTest extends TestCase
{
    /**
     * @var Properties
     */
    protected $properties;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->properties = new Properties();
    }

    /**
     * @covers \Arris\Core\Config\Parser\Properties::getSupportedExtensions()
     */
    public function testGetSupportedExtensions()
    {
        $expected = ['properties'];
        $actual = $this->properties->getSupportedExtensions();
        $this->assertSame($expected, $actual);
    }

    /**
     * @covers \Arris\Core\Config\Parser\Properties::parseFile()
     * @covers \Arris\Core\Config\Parser\Properties::parseString()
     * @covers \Arris\Core\Config\Parser\Properties::parse()
     */
    public function testLoadProperties()
    {
        $config = $this->properties->parseFile(__DIR__.'/../mocks/pass/config.properties');

        $this->assertSame('https://en.wikipedia.org/', @$config['website']);
        $this->assertSame('English', @$config['language']);
        $this->assertSame('Welcome to Wikipedia!', @$config['message']);
        $this->assertSame('valueOverOneLine\\', @$config['key']);
        $this->assertSame('This is the value that could be looked up with the key "key with spaces".', @$config['key with spaces']);
        $this->assertSame('This is the value for the key "key:with=colonAndEqualsSign"', @$config['key:with=colonAndEqualsSign']);
        $this->assertSame('c:\\wiki\\templates', @$config['path']);
    }

    /**
     * @covers \Arris\Core\Config\Parser\Ini::parseFile()
     * @covers \Arris\Core\Config\Parser\Ini::parse()
     */
    public function testLoadInvalidIniGBH()
    {
        $config = $this->properties->parseFile(__DIR__.'/../mocks/fail/error.properties');

        $this->assertEmpty($config);
    }
}
