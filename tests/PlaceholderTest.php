<?php
use Opis\Utils\Placeholder;

class PlaceholderTest
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
}
