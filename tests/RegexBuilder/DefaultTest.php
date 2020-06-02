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

class DefaultTest extends TestCase
{
    /** @var RegexBuilder */
    private static $pathBuilder;

    /** @var RegexBuilder */
    private static $domainBuilder;

    public static function setUpBeforeClass(): void
    {
        self::$pathBuilder = new RegexBuilder();
        self::$domainBuilder = new RegexBuilder([
            RegexBuilder::CAPTURE_MODE => RegexBuilder::CAPTURE_RIGHT | RegexBuilder::ALLOW_OPT_TRAIL,
            RegexBuilder::SEPARATOR_SYMBOL => '.',
        ]);
    }

    protected function runBuilder(
        RegexBuilder $builder,
        string $pattern,
        array $placeholders = [],
        string $expected = null,
        array $tests = null
    ) {
        $regex = $builder->getRegex($pattern, $placeholders);
        if ($expected) {
            $this->assertEquals($expected, $regex, $pattern . ' should be ' . $regex);
        }
        if ($tests) {
            foreach ($tests as $test => $valid) {
                $this->assertEquals($valid, (bool)preg_match($regex, $test),
                    $test . ($valid ? '' : ' not ') . ' matches ' . $pattern);
            }
        }
    }

    /**
     * @dataProvider pathProvider
     */
    public function testLeftCapture($pattern, $placeholders, $expected, $tests = null)
    {
        $this->runBuilder(self::$pathBuilder, $pattern, $placeholders, $expected, $tests);
    }

    /**
     * @dataProvider domainProvider
     */
    public function testRightCapture($pattern, $placeholders, $expected, $tests = null)
    {
        $this->runBuilder(self::$domainBuilder, $pattern, $placeholders, $expected, $tests);
    }

    public function pathProvider()
    {
        return [

            [
                '/',
                [],
                '~^/?$~u',
                [
                    '' => true,
                    '/' => true,
                    ' ' => false,
                    '2' => false,
                    '2/' => false,
                ],
            ],
            [
                '//',
                [],
                '~^//?$~u',
                [
                    '/' => true,
                    '//' => true,
                    '///' => false,
                    '' => false,
                    ' ' => false,
                    ' /' => false,
                    '2' => false,
                    '2/' => false,
                    '/2/' => false,
                    '2//' => false,
                ],
            ],
            [
                '///',
                [],
                '~^///?$~u',
                [
                    '//' => true,
                    '///' => true,
                    '////' => false,
                    '///2' => false,
                    '' => false,
                    '/' => false,
                    '/2/' => false,
                    ' ' => false,
                    ' /' => false,
                ],
            ],
            [
                '/a',
                [],
                '~^/a/?$~u',
                [
                    '/a' => true,
                    '/a/' => true,
                    '/b' => false,
                    'a' => false,
                    'a/' => false,
                    'a//' => false,
                    '' => false,
                ],
            ],
            [
                '/a/b',
                [],
                '~^/a/b/?$~u',
                [
                    '/a/b' => true,
                    '/a/b/' => true,
                    'a/b' => false,
                    '//a/b' => false,
                    '/a//b' => false,
                    '/a/b//' => false,
                ],
            ],
            [
                'a/b/c',
                [],
                '~^a/b/c/?$~u',
                [
                    'a/b/c' => true,
                    'a/b/c/' => true,
                    '/a/b/c' => false,
                    '/a/b/c/' => false,
                    'a/b/' => false,
                    'a/c/b' => false,
                ],
            ],
            [
                'a/b/',
                [],
                '~^a/b/?$~u',
                [
                    'a/b' => true,
                    'a/b/' => true,
                    '/a/b' => false,
                    '/a/b/c' => false,
                    'b/a' => false,
                ],
            ],
            [
                'a',
                [],
                '~^a/?$~u',
                [
                    'a' => true,
                    'a/' => true,
                    '/a' => false,
                    '' => false,
                    'b' => false,
                ],
            ],
            [
                '',
                [],
                '~^/?$~u',
                [
                    '' => true,
                    '/' => true,
                    '//' => false,
                    ' /' => false,
                    'a' => false,
                ],
            ],

            [
                '{a}',
                ['a' => '\d'],
                '~^(?P<a>(?:\d))/?$~u',
                [
                    '2' => true,
                    '5/' => true,
                    '' => false,
                    'a' => false,
                    'a/' => false,
                    '10' => false,
                    '11/' => false,
                ],
            ],
            [
                '{a?}',
                ['a' => '\d'],
                '~^(?P<a>(?:\d))?/?$~u',
                [
                    '' => true,
                    '/' => true,
                    '2' => true,
                    '5/' => true,
                    'a' => false,
                    'a/' => false,
                    '10' => false,
                    '11/' => false,
                ],
            ],

            [
                '/{a}',
                ['a' => '\d'],
                '~^(?:/(?P<a>(?:\d)))/?$~u',
                [
                    '/2' => true,
                    '/5/' => true,
                    '' => false,
                    '/' => false,
                    'a' => false,
                    '/a' => false,
                    '/a/' => false,
                    '/10' => false,
                    '/11/' => false,
                ],
            ],
            [
                '/{a?}',
                ['a' => '\d'],
                '~^(?:/(?P<a>(?:\d)))?/?$~u',
                [
                    '' => true,
                    '/' => true,
                    '/2' => true,
                    '/5/' => true,
                    'a' => false,
                    '//' => false,
                    '/a' => false,
                    '/a/' => false,
                    '/10' => false,
                    '/11/' => false,
                ],
            ],

            [
                '/{a}/',
                ['a' => '\d'],
                '~^(?:/(?P<a>(?:\d)))/?$~u',
                [
                    '/2' => true,
                    '/5/' => true,
                    '' => false,
                    '/' => false,
                    'a' => false,
                    '/a' => false,
                    '/a/' => false,
                    '/10' => false,
                    '/11/' => false,
                ],
            ],
            [
                '/{a?}/',
                ['a' => '\d'],
                '~^(?:/(?P<a>(?:\d)))?/?$~u',
                [
                    '' => true,
                    '/' => true,
                    '/2' => true,
                    '/5/' => true,
                    'a' => false,
                    '//' => false,
                    '/a' => false,
                    '/a/' => false,
                    '/10' => false,
                    '/11/' => false,
                ],
            ],

            [
                '{a}-suf',
                ['a' => '\d'],
                '~^(?P<a>(?:\d))\-suf/?$~u',
                [
                    '2-suf' => true,
                    '5-suf/' => true,
                    'a-suf' => false,
                    '-suf' => false,
                    '' => false,
                    '/' => false,
                    ' 2-suf' => false,
                ],
            ],
            [
                '{a?}-suf',
                ['a' => '\d'],
                '~^(?P<a>(?:\d))?\-suf/?$~u',
                [
                    '2-suf' => true,
                    '5-suf/' => true,
                    '-suf' => true,
                    '-suf/' => true,
                    'a-suf' => false,
                    '' => false,
                    '/' => false,
                    'suf' => false,
                    ' 2-suf' => false,
                ],
            ],

            [
                'pre-{a}',
                ['a' => '\d'],
                '~^pre\-(?P<a>(?:\d))/?$~u',
                [
                    'pre-1' => true,
                    'pre-5/' => true,
                    '' => false,
                    '/' => false,
                    'pre' => false,
                    'pre-a' => false,
                    'pre-' => false,
                ],
            ],
            [
                'pre-{a?}',
                ['a' => '\d'],
                '~^pre\-(?P<a>(?:\d))?/?$~u',
                [
                    'pre-1' => true,
                    'pre-5/' => true,
                    'pre-' => true,
                    'pre-/' => true,
                    '' => false,
                    '/' => false,
                    'pre' => false,
                    'pre-a' => false,
                    'pre-//' => false,
                ],
            ],

            [
                'pre-{a}-suf',
                ['a' => '\d'],
                '~^pre\-(?P<a>(?:\d))\-suf/?$~u',
                [
                    'pre-1-suf' => true,
                    'pre-5-suf/' => true,
                    'pre-a-suf' => false,
                    'pre-suf' => false,
                    'pre--suf' => false,
                    'pre-1' => false,
                    'pre-1-' => false,
                    '1-suf' => false,
                    '-2-suf/' => false,
                ],
            ],
            [
                'pre-{a?}-suf',
                ['a' => '\d'],
                '~^pre\-(?P<a>(?:\d))?\-suf/?$~u',
                [
                    'pre-1-suf' => true,
                    'pre-5-suf/' => true,
                    'pre--suf' => true,
                    'pre-a-suf' => false,
                    'pre-suf' => false,
                    'pre-1' => false,
                    'pre-1-' => false,
                    '1-suf' => false,
                    '-2-suf/' => false,
                ],
            ],

            [
                '{a}/suf',
                ['a' => '\d'],
                '~^(?P<a>(?:\d))/suf/?$~u',
                [
                    '2/suf' => true,
                    '5/suf/' => true,
                    'a/suf' => false,
                    '/suf' => false,
                    '' => false,
                    '10/suf' => false,
                ],
            ],
            [
                '{a?}/suf',
                ['a' => '\d'],
                '~^(?P<a>(?:\d))?/suf/?$~u',
                [
                    '2/suf' => true,
                    '5/suf/' => true,
                    '/suf' => true,
                    '/suf/' => true,
                    'a/suf' => false,
                    '' => false,
                    '10/suf' => false,
                ],
            ],

            [
                '{a}/suf/',
                ['a' => '\d'],
                '~^(?P<a>(?:\d))/suf/?$~u',
                [
                    '2/suf' => true,
                    '5/suf/' => true,
                    'a/suf' => false,
                    '/suf' => false,
                    '' => false,
                    '10/suf' => false,
                ],
            ],
            [
                '{a?}/suf/',
                ['a' => '\d'],
                '~^(?P<a>(?:\d))?/suf/?$~u',
                [
                    '2/suf' => true,
                    '5/suf/' => true,
                    '/suf' => true,
                    '/suf/' => true,
                    'a/suf' => false,
                    '' => false,
                    '10/suf' => false,
                ],
            ],

            [
                'pre/{a}',
                ['a' => '\d'],
                '~^pre(?:/(?P<a>(?:\d)))/?$~u',
                [
                    'pre/2' => true,
                    'pre/5/' => true,
                    'pre/a' => false,
                    'pre//' => false,
                    'pre/' => false,
                    'pre' => false,
                    'pre/10' => false,
                    '2' => false,
                    '' => false,
                    '/' => false,
                    '/2' => false,
                ],
            ],
            [
                'pre/{a?}',
                ['a' => '\d'],
                '~^pre(?:/(?P<a>(?:\d)))?/?$~u',
                [
                    'pre/2' => true,
                    'pre/5/' => true,
                    'pre' => true,
                    'pre/' => true,

                    'pre/a' => false,
                    'pre//' => false,
                    'pre/10' => false,
                    '2' => false,
                    '' => false,
                    '/' => false,
                    '/2' => false,
                ],
            ],

            [
                'pre/{a}/',
                ['a' => '\d'],
                '~^pre(?:/(?P<a>(?:\d)))/?$~u',
                [
                    'pre/2' => true,
                    'pre/5/' => true,
                    'pre/a' => false,
                    'pre//' => false,
                    'pre/' => false,
                    'pre' => false,
                    'pre/10' => false,
                    '2' => false,
                    '' => false,
                    '/' => false,
                    '/2' => false,
                ],
            ],
            [
                'pre/{a?}/',
                ['a' => '\d'],
                '~^pre(?:/(?P<a>(?:\d)))?/?$~u',
                [
                    'pre/2' => true,
                    'pre/5/' => true,
                    'pre' => true,
                    'pre/' => true,

                    'pre/a' => false,
                    'pre//' => false,
                    'pre/10' => false,
                    '2' => false,
                    '' => false,
                    '/' => false,
                    '/2' => false,
                ],
            ],

            [
                'pre/{a}/suf',
                ['a' => '\d'],
                '~^pre(?:/(?P<a>(?:\d)))/suf/?$~u',
                [
                    'pre/2/suf' => true,
                    'pre/5/suf/' => true,
                    'pre/a' => false,
                    'pre/a/suf' => false,
                    'pre/suf' => false,
                    'pre//suf' => false,
                    '2/suf' => false,
                    'pre/10/suf' => false,
                    '2' => false,
                    '' => false,
                    '/' => false,
                ],
            ],
            [
                'pre/{a?}/suf',
                ['a' => '\d'],
                '~^pre(?:/(?P<a>(?:\d)))?/suf/?$~u',
                [
                    'pre/2/suf' => true,
                    'pre/5/suf/' => true,
                    'pre/suf' => true,
                    'pre/suf/' => true,

                    'pre//suf' => false,
                    'pre/a/suf' => false,
                    '2/suf' => false,
                    'pre/10/suf' => false,
                    '2' => false,
                    '' => false,
                    '/' => false,
                ],
            ],

            [
                'pre/{a}/suf/',
                ['a' => '\d'],
                '~^pre(?:/(?P<a>(?:\d)))/suf/?$~u',
                [
                    'pre/2/suf' => true,
                    'pre/5/suf/' => true,
                    'pre/a' => false,
                    'pre/a/suf' => false,
                    'pre/suf' => false,
                    'pre//suf' => false,
                    '2/suf' => false,
                    'pre/10/suf' => false,
                    '2' => false,
                    '' => false,
                    '/' => false,
                ],
            ],
            [
                'pre/{a?}/suf/',
                ['a' => '\d'],
                '~^pre(?:/(?P<a>(?:\d)))?/suf/?$~u',
                [
                    'pre/2/suf' => true,
                    'pre/5/suf/' => true,
                    'pre/suf' => true,
                    'pre/suf/' => true,

                    'pre//suf' => false,
                    'pre/a/suf' => false,
                    '2/suf' => false,
                    'pre/10/suf' => false,
                    '2' => false,
                    '' => false,
                    '/' => false,
                ],
            ],

            [
                '//pre//{a}//suf/',
                ['a' => '\d'],
                '~^//pre/(?:/(?P<a>(?:\d)))//suf/?$~u',
                [
                    '//pre//2//suf' => true,
                    '//pre//5//suf/' => true,

                    '//pre//5//suf//' => false,
                    '//pre///suf' => false,
                    '//pre//suf' => false,
                    '//pre/suf' => false,
                ],
            ],
            [
                '//pre//{a?}//suf/',
                ['a' => '\d'],
                '~^//pre/(?:/(?P<a>(?:\d)))?//suf/?$~u',
                [
                    '//pre//2//suf' => true,
                    '//pre//5//suf/' => true,
                    '//pre///suf' => true,
                    '//pre///suf/' => true,

                    '//pre////suf' => false,
                    '//pre//5//suf//' => false,
                    '//pre//suf' => false,
                    '//pre/suf' => false,
                ],
            ],

            [
                '//pre//{a}//suf//',
                ['a' => '\d'],
                '~^//pre/(?:/(?P<a>(?:\d)))//suf//?$~u',
                [
                    '//pre//2//suf/' => true,
                    '//pre//5//suf//' => true,

                    '//pre//2//suf' => false,
                    '//pre///suf' => false,
                    '//pre//suf' => false,
                    '//pre/suf' => false,
                ],
            ],
            [
                '//pre//{a?}//suf//',
                ['a' => '\d'],
                '~^//pre/(?:/(?P<a>(?:\d)))?//suf//?$~u',
                [
                    '//pre//2//suf/' => true,
                    '//pre//5//suf//' => true,
                    '//pre///suf/' => true,
                    '//pre///suf//' => true,

                    '//pre//3//suf' => false,
                    '//pre////suf' => false,
                    '//pre//5//suf' => false,
                    '//pre//suf' => false,
                    '//pre/suf' => false,
                ],
            ],


            [
                '{a}/{b}',
                ['a' => '\d', 'b' => '[a-z]'],
                '~^(?P<a>(?:\d))(?:/(?P<b>(?:[a-z])))/?$~u',
                [
                    '2/a' => true,
                    '5/z/' => true,

                    '2' => false,
                    '5/' => false,
                    '/a' => false,
                    '10/a' => false,
                    '5/ab' => false,
                ],
            ],
            [
                '{a}/{b?}',
                ['a' => '\d', 'b' => '[a-z]'],
                '~^(?P<a>(?:\d))(?:/(?P<b>(?:[a-z])))?/?$~u',
                [
                    '2/a' => true,
                    '5/z/' => true,
                    '2' => true,
                    '5/' => true,

                    '/a' => false,
                    '10/a' => false,
                    '5/ab' => false,
                    '10' => false,
                ],
            ],
            [
                '{a?}/{b}',
                ['a' => '\d', 'b' => '[a-z]'],
                '~^(?P<a>(?:\d))?(?:/(?P<b>(?:[a-z])))/?$~u',
                [
                    '2/a' => true,
                    '5/z/' => true,
                    '/a' => true,
                    '/z/' => true,

                    '10/a' => false,
                    '5/ab' => false,
                    '10' => false,
                    '1' => false,
                    '5/' => false,
                    'z' => false,
                ],
            ],
            [
                '{a?}/{b?}',
                ['a' => '\d', 'b' => '[a-z]'],
                '~^(?P<a>(?:\d))?(?:/(?P<b>(?:[a-z])))?/?$~u',
                [
                    '2/a' => true,
                    '5/z/' => true,
                    '/a' => true,
                    '/z/' => true,
                    '1' => true,
                    '5/' => true,

                    '10/a' => false,
                    '5/ab' => false,
                    '10' => false,
                    'z' => false,
                    'z/' => false,
                ],
            ],

            [
                'pre-{a?}/test/{b?}-suf',
                ['a' => '\d', 'b' => '[a-z]'],
                '~^pre\-(?P<a>(?:\d))?/test/(?P<b>(?:[a-z]))?\-suf/?$~u',
                [
                    'pre-2/test/a-suf' => true,
                    'pre-5/test/z-suf/' => true,
                    'pre-/test/z-suf' => true,
                    'pre-/test/z-suf/' => true,
                    'pre-2/test/-suf' => true,
                    'pre-5/test/-suf/' => true,
                    'pre-/test/-suf' => true,
                    'pre-/test/-suf/' => true,

                    'pre-a/test/2-suf' => false,
                    'pre-/test/2-suf' => false,
                    'pre-a/test/-suf' => false,
                ],
            ],

            [
                '{a}{b}/{c?}{d?}',
                ['a' => '\d', 'b' => '[a-z]', 'c' => '\d', 'd' => '[a-z]'],
                '~^(?P<a>(?:\d))(?P<b>(?:[a-z]))/(?P<c>(?:\d))?(?P<d>(?:[a-z]))?/?$~u',
                [
                    '2a/3z' => true,
                    '5b/0t/' => true,
                    '8c' => false, // not a segment, delimiter must be added
                    '8c/' => true,
                    '8c//' => true,
                    '8c/2' => true,
                    '8c/2/' => true,
                    '8c/c' => true,
                    '8c/c/' => true,
                ],
            ],

            [
                '{a}{b}/{cd?}',
                ['a' => '\d', 'b' => '[a-z]', 'cd' => '\d?[a-z]?'],
                '~^(?P<a>(?:\d))(?P<b>(?:[a-z]))(?:/(?P<cd>(?:\d?[a-z]?)))?/?$~u',
                [
                    '2a/3z' => true,
                    '5b/0t/' => true,
                    '8c' => true,
                    '8c/' => true,
                    '8c//' => true,
                    '8c/2' => true,
                    '8c/2/' => true,
                    '8c/c' => true,
                    '8c/c/' => true,
                ],
            ],

            [
                '{a}{b?}/{c?}{d}',
                ['a' => '\d', 'b' => '[a-z]', 'c' => '\d', 'd' => '[a-z]'],
                '~^(?P<a>(?:\d))(?P<b>(?:[a-z]))?/(?P<c>(?:\d))?(?P<d>(?:[a-z]))/?$~u',
                [
                    '2a/3z' => true,
                    '5b/0t/' => true,
                    '8c/a' => true,
                    '8c/z/' => true,
                    '8/5c' => true,
                    '8/5c/' => true,
                    '8/c' => true,
                    '8/c/' => true,
                    '8/3' => false,
                    'b/3a' => false,
                    'b/a' => false,
                ],
            ],
        ];
    }

    public function domainProvider()
    {
        return [

            [
                '.',
                [],
                '~^\.?$~u',
                [
                    '.' => true,
                    '' => true,
                    '..' => false,
                ],
            ],
            [
                '..',
                [],
                '~^\.\.?$~u',
                [
                    '.' => true,
                    '..' => true,
                    '' => false,
                    '...' => false,
                ],
            ],
            [
                'a.b.c',
                [],
                '~^a\.b\.c\.?$~u',
                [
                    'a.b.c' => true,
                    'a.b.c.' => true,
                    'a.c.b' => false,
                ],
            ],

            [
                '{a?}.{b}',
                ['a' => '[a-z]+'],
                '~^(?:(?P<a>(?:[a-z]+))\.)?(?P<b>(?:[^\.]+))\.?$~u',
                [
                    'test.com' => true,
                    'test.com.' => true,
                    'com' => true,
                    'com.' => true,
                    '.com' => false,
                    '.com.' => false,
                    '.com.a' => false,
                    '' => false,
                ],
            ],
            [
                '{a?}.{b?}',
                ['a' => '[a-z]+'],
                '~^(?:(?P<a>(?:[a-z]+))\.)?(?P<b>(?:[^\.]+))?\.?$~u',
                [
                    '' => true,
                    '.' => true,
                    'test.com' => true,
                    'test.com.' => true,
                    'test.' => true,
                    'test..' => true,
                    'test.com.t' => false,
                    '.com' => false,
                    '.com.' => false,
                ],
            ],
            [
                '{a}.{b?}',
                ['a' => '[a-z]+'],
                '~^(?:(?P<a>(?:[a-z]+))\.)(?P<b>(?:[^\.]+))?\.?$~u',
                [
                    'test.com' => true,
                    'test.com.' => true,
                    'test.' => true,
                    'test..' => true,
                    'test' => false,
                    '' => false,
                    '1.com' => false,
                    '.com' => false,
                    '.com.' => false,
                ],
            ],
            [
                'x.{a}.z',
                ['a' => '[a-z]'],
                '~^x\.(?:(?P<a>(?:[a-z]))\.)z\.?$~u',
                [
                    'x.a.z' => true,
                    'x.b.z.' => true,
                    'x..z' => false,
                    'x.z' => false,
                ],
            ],
            [
                'x.{a?}.z',
                ['a' => '[a-z]'],
                '~^x\.(?:(?P<a>(?:[a-z]))\.)?z\.?$~u',
                [
                    'x.a.z' => true,
                    'x.b.z.' => true,
                    'x.z' => true,
                    'x.z.' => true,
                    'x..z' => false,
                    'x..z.' => false,
                ],
            ],

            [
                'pre-{a}.{b}',
                [],
                '~^pre\-(?P<a>(?:[^\.]+))\.(?P<b>(?:[^\.]+))\.?$~u',
                [
                    'pre-a.b' => true,
                    'pre-a.b.' => true,
                    'pre-.' => false,
                    'pre-a' => false,
                    'pre-a.' => false,
                ],
            ],
            [
                'pre-{a?}.{b}',
                [],
                '~^pre\-(?P<a>(?:[^\.]+))?\.(?P<b>(?:[^\.]+))\.?$~u',
                [
                    'pre-a.b' => true,
                    'pre-a.b.' => true,
                    'pre-.b' => true,
                    'pre-.b.' => true,
                    'pre-b' => false,
                    'pre-a' => false,
                    'pre-a.' => false,
                ],
            ],
            [
                'pre-{a?}.{b?}',
                [],
                '~^pre\-(?P<a>(?:[^\.]+))?\.(?P<b>(?:[^\.]+))?\.?$~u',
                [
                    'pre-a.b' => true,
                    'pre-a.b.' => true,
                    'pre-.b' => true,
                    'pre-.b.' => true,
                    'pre-a.' => true,
                    'pre-a..' => true,
                    'pre-.' => true,
                    'pre-..' => true,
                    'pre-' => false,
                ],
            ],
            [
                '{a?}.ex{n?}.c',
                ['a' => '[a-z0-9]+', 'n' => '\d'],
                '~^(?:(?P<a>(?:[a-z0-9]+))\.)?ex(?P<n>(?:\d))?\.c\.?$~u',
                [
                    'a.ex0.c' => true,
                    'a.ex0.c.' => true,
                    'a0.ex3.c' => true,
                    'a0.ex3.c.' => true,
                    'ex1.c' => true,
                    'ex2.c.' => true,
                    'ex.c' => true,
                    'ex.c.' => true,
                    'a.ex.c' => true,
                    'ab.ex.c.' => true,

                    '.ex1.c' => false,
                    '.ex.c' => false,
                    'a.exa.c' => false,
                ],
            ],
        ];
    }
}