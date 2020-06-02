<?php
namespace Opis\Utils\Test;

use Opis\Utils\Placeholder;
use PHPUnit\Framework\TestCase;

class PlaceholderTest extends TestCase
{
    protected $placeholder;

    public function setUp(): void
    {
        $this->placeholder = new Placeholder();
    }

    public function testBasicReplace()
    {
        $this->assertEquals('Hello world', $this->placeholder->replace('Hello ${foo}', [
            'foo' => 'world'
        ]));
    }

    public function testReplaceMultiple()
    {
        $this->assertEquals('Hello world from world', $this->placeholder->replace('Hello ${foo} from ${foo}', [
            'foo' => 'world'
        ]));
    }

    public function testReplaceMultipleDifferent()
    {
        $this->assertEquals('Hello world from Opis', $this->placeholder->replace('Hello ${foo} from ${bar}', [
            'foo' => 'world',
            'bar' => 'Opis'
        ]));
    }

    public function testNotReplace()
    {
        $this->assertEquals('Hello ${foo}', $this->placeholder->replace('Hello ${foo}', [
            'bar' => 'world'
        ]));
    }
}
