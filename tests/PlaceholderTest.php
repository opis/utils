<?php
use Opis\Utils\Placeholder;

class PlaceholderTest extends PHPUnit_Framework_TestCase
{
    protected $placeholder;

    public function setUp()
    {
        $this->placeholder = new Placeholder();
    }

    public function testBasicReplace1()
    {
        $this->assertEquals('Hello world', $this->placeholder->replace('Hello @foo', array('@foo' => 'world')));
    }

    public function testBasicReplace2()
    {
        $this->assertEquals('Hello world', $this->placeholder->replace('Hello %foo', array('%foo' => 'world')));
    }

    public function testBasicReplace3()
    {
        $this->assertEquals('Hello &lt;world&gt;', $this->placeholder->replace('Hello @foo', array('@foo' => '<world>')));
    }

    public function testBasicReplace4()
    {
        $this->assertEquals('Hello <world>', $this->placeholder->replace('Hello %foo', array('%foo' => '<world>')));
    }

    public function testBasicReplace5()
    {
        $this->assertEquals('Hello <world>', $this->placeholder->replace('Hello @foo', array('@foo' => '<world>'), false));
    }
}
