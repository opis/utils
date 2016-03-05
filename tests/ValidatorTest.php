<?php
use Opis\Utils\Validator;

class ValidatorTest extends PHPUnit_Framework_TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new Validator();
    }

    public function testBasicValidation1()
    {
        $this->assertEquals('foo', $this->validator->validate('Field', 'foo'));
    }

    public function testBasicValidation2()
    {
        $this->assertEquals('FOO', $this->validator->validate('Field', 'foo', 'strtoupper'));
    }
}
