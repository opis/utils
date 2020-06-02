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

namespace Opis\Utils\Test\RegexBuilder;

use Opis\Utils\RegexBuilder;
use PHPUnit\Framework\TestCase;

class AssignTest extends TestCase
{
    /** @var RegexBuilder */
    private static $builder;

    public static function setUpBeforeClass(): void
    {
        self::$builder = new RegexBuilder();
    }

    public function testAssignError()
    {
        $builder = self::$builder;
        $this->expectException(\RuntimeException::class);
        // Unescaped regex delimiter
        $builder->getRegex("{a?=\d~a}");
    }

    /**
     * @dataProvider assignDataProvider
     */
    public function testAssign($pattern, $placeholders, $tests)
    {
        $builder = self::$builder;
        $regex = $builder->getRegex($pattern, $placeholders);

        foreach ($tests as $value => $valid) {
            $this->assertEquals($valid, $builder->matches($regex, $value), "{$pattern} => {$value}");
        }

        $this->assertTrue(true);
    }

    public function assignDataProvider()
    {
        return [
            [
                '{a=\d{2,3}}',
                [],
                [
                    '11' => true,
                    '111' => true,
                    '2' => false,
                    '' => false,
                    '1111' => false,
                    'aa' => false,
                ],
            ],
            [
                '{a?=\d{2,3}}',
                [],
                [
                    '11' => true,
                    '111' => true,
                    '2' => false,
                    '' => true,
                    '1111' => false,
                    'aa' => false,
                ],
            ],
            [
                '{a=\d}',
                ['a' => '[a-z]'],
                [
                    'a' => true,
                    'z' => true,
                    '1' => false,
                    '' => false,
                ],
            ],
            [
                '{a?=\d}',
                ['a' => '[a-z]'],
                [
                    'a' => true,
                    'z' => true,
                    '' => true,
                    '1' => false,
                ],
            ],
            [
                '/{a?=cent(re|er)}/{b=\d+}',
                [],
                [
                    '/center/1' => true,
                    '/centre/23' => true,
                    '/1' => true,
                    '/123' => true,
                    'a/12' => false,
                    '/cent/123' => false,
                ],
            ],
            [
                '/{a?=cent(re|er)}/{b=\d+}',
                ['a' => '.*'],
                [
                    '/center/1' => true,
                    '/centre/23' => true,
                    '/1' => true,
                    '/123' => true,
                    '/cent/123' => true,
                    '/abc/def/123' => true,
                ],
            ],
            [
                '{a=\d}{b?=-?abc}/{c=[=]}',
                [],
                [
                    '1-abc/=' => true,
                    '2abc/=' > true,
                    '5/=' => true,
                    'a/=' => false,
                    '1/' => false,
                ],
            ],
            [
                '{=\d}{a?=[a-z]+}',
                [],
                [
                    '2a' => true,
                    '0abc' => true,
                    'b' => false,
                    '22a' => false,
                ],
            ],
            [
                '{?=\d}{a?=[a-z]+}',
                [],
                [
                    '2a' => true,
                    '0abc' => true,
                    'b' => true,
                    '22a' => false,
                ],
            ],
        ];
    }
}