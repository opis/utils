<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

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
