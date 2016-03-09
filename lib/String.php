<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2015-2016 Marius Sarca
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

namespace Opis\Utils;

use Exception;
use ArrayAccess;

class String implements ArrayAccess
{
    protected $chars;
    protected $codepoints;
    protected $length;
    protected $str;
    protected $byteLength;
    protected static $codes;
    protected static $ords;

    const UNICODE_REPLACEMENT_CODEPOINT = 0xFFFD;
    const UTF8_REPLACEMENT_CHARACTER = "\xEF\xBF\xBD";

    public function __construct($bytes)
    {
        if ($bytes === null) {
            return;
        }

        $codes = &static::getCodes();

        $utf8 = array();
        $l = strlen($bytes);
        $p = 0;
        $hasErrors = false;

        do {
            $cu1 = $codes[$bytes[$p++]];

            if ($cu1 < 0x80) {
                $utf8[] = $cu1;
            } elseif ($cu1 < 0xC2) {
                $hasErrors = true;
                $utf8[] = UNICODE_REPLACEMENT_CODEPOINT; //$cu1 + 0xDC00;
            } elseif ($cu1 < 0xE0) {
                $cu2 = $codes[$bytes[$p++]];

                if (($cu2 & 0xC0) != 0x80) {
                    $p--;
                    $utf8[] = UNICODE_REPLACEMENT_CODEPOINT; //$cu1 + 0xDC00;
                    $hasErrors = true;
                    continue;
                }

                $utf8[] = ($cu1 << 6) + $cu2 - 0x3080;
            } elseif ($cu1 < 0xF0) {
                $cu2 = $codes[$bytes[$p++]];

                if ((($cu2 & 0xC0) != 0x80) || ($cu1 == 0xE0 && $cu2 < 0xA0)) {
                    $p--;
                    $utf8[] = UNICODE_REPLACEMENT_CODEPOINT; //$cu1 + 0xDC00;
                    $hasErrors = true;
                    continue;
                }

                $cu3 = $codes[$bytes[$p++]];

                if (($cu3 & 0xC0) != 0x80) {
                    $p -= 2;
                    $utf8[] = UNICODE_REPLACEMENT_CODEPOINT; //$cu1 + 0xDC00;
                    $hasErrors = true;
                    continue;
                }

                $utf8[] = ($cu1 << 12) + ($cu2 << 6) + $cu3 - 0xE2080;
            } elseif ($cu1 < 0xF5) {
                $cu2 = $codes[$bytes[$p++]];

                if ((($cu2 & 0xC0) != 0x80) || ($cu1 == 0xF0 && $cu2 < 0x90) || ($cu1 == 0xF4 && $cu2 >= 0x90)) {
                    $p--;
                    $utf8[] = UNICODE_REPLACEMENT_CODEPOINT; //$cu1 + 0xDC00;
                    $hasErrors = true;
                    continue;
                }

                $cu3 = $codes[$bytes[$p++]];

                if (($cu3 & 0xC0) != 0x80) {
                    $p -= 2;
                    $utf8[] = UNICODE_REPLACEMENT_CODEPOINT; //$cu1 + 0xDC00;
                    $hasErrors = true;
                    continue;
                }

                $cu4 = $codes[$bytes[$p++]];

                if (($cu4 & 0xC0) != 0x80) {
                    $p -= 3;
                    $utf8[] = UNICODE_REPLACEMENT_CODEPOINT; //$cu1 + 0xDC00;
                    $hasErrors = true;
                    continue;
                }

                $utf8[] = ($cu1 << 18) + ($cu2 << 12) + ($cu3 << 6) + $cu4 - 0x3C82080;
            } else {
                $utf8[] = UNICODE_REPLACEMENT_CODEPOINT; //$cu1 + 0xDC00;
                $hasErrors = true;
            }
        } while ($p < $l);

        $this->codepoints = &$utf8;
        $this->length = count($utf8);
        if (!$hasErrors) {
            $this->str = $bytes;
        }
    }

    public function length()
    {
        return $this->length;
    }

    public function byteLength()
    {
        if ($this->byteLength === null) {
            $this->byteLength = strlen((string) $this);
        }

        return $this->byteLength;
    }

    public function offsetExists($index)
    {
        return isset($this->codepoints[$index]);
    }

    public function offsetGet($index)
    {
        $chars = &$this->chars;

        if (!isset($chars[$index])) {
            $cp = $this->codepoints[$index];

            if ($cp < 0x80) {
                $chars[$index] = chr($cp);
            } elseif ($cp <= 0x7FF) {
                $chars[$index] = chr(($cp >> 6) + 0xC0) . chr(($cp & 0x3F) + 0x80);
            } elseif ($cp <= 0xFFFF) {
                $chars[$index] = chr(($cp >> 12) + 0xE0) . chr((($cp >> 6) & 0x3F) + 0x80) . chr(($cp & 0x3F) + 0x80);
            } elseif ($cp <= 0x10FFFF) {
                $chars[$index] = chr(($cp >> 18) + 0xF0) . chr((($cp >> 12) & 0x3F) + 0x80)
                    . chr((($cp >> 6) & 0x3F) + 0x80) . chr(($cp & 0x3F) + 0x80);
            } else {
                throw new Exception("Invalid UTF-8");
            }
        }

        return $chars[$index];
    }

    public function offsetSet($index, $value)
    {
        
    }

    public function offsetUnset($index)
    {
        
    }

    public function __invoke($index)
    {
        return $this->codepoints[$index];
    }

    public function &getCodePoints()
    {
        return $this->codepoints;
    }

    public function __toString()
    {
        if ($this->str === null) {
            $this->str = '';

            for ($i = 0, $l = $this->length; $i < $l; $i++) {
                $this->str .= $this->offsetGet($i);
            }
        }

        return $this->str;
    }

    public function substr($start, $length = null)
    {
        return $this->substring($start, $length);
    }

    public function substring($start, $length = null)
    {
        $str = new static(null);
        $str->codepoints = array_slice($this->codepoints, $start, $length);
        $str->length = count($str->codepoints);
        return $str;
    }

    public function trim($character_mask = " \t\n\r\0\x0B")
    {
        if (!($character_mask instanceof static)) {
            $character_mask = new static($character_mask);
        }

        $cm = &$character_mask->codepoints;
        $cp = &$this->codepoints;
        $l = count($cm);
        $start = 0;
        $end = $this->length;


        for ($i = 0; $i < $this->length; $i++) {
            if (!in_array($cp[$i], $cm)) {
                break;
            }
        }

        $start = $i;

        for ($i = $this->length - 1; $i > $start; $i--) {
            if (!in_array($cp[$i], $cm)) {
                break;
            }
        }

        $end = $i + 1;

        $str = new static(null);
        $str->codepoints = array_slice($cp, $start, $end - $start);
        $str->length = count($str->codepoints);
        return $str;
    }

    public function ltrim($character_mask = " \t\n\r\0\x0B")
    {
        if (!($character_mask instanceof static)) {
            $character_mask = new static($character_mask);
        }

        $cm = &$character_mask->codepoints;
        $cp = &$this->codepoints;
        $l = count($cm);
        $start = 0;
        $end = $this->length;

        for ($i = 0; $i < $this->length; $i++) {
            if (!in_array($cp[$i], $cm)) {
                break;
            }
        }

        $start = $i;

        $str = new static(null);
        $str->codepoints = array_slice($cp, $start, $end - $start);
        $str->length = count($str->codepoints);
        return $str;
    }

    public function rtrim($character_mask = " \t\n\r\0\x0B")
    {
        if (!($character_mask instanceof static)) {
            $character_mask = new static($character_mask);
        }

        $cm = &$character_mask->codepoints;
        $cp = &$this->codepoints;
        $l = count($cm);
        $start = 0;
        $end = $this->length;

        for ($i = $this->length - 1; $i > $start; $i--) {
            if (!in_array($cp[$i], $cm)) {
                break;
            }
        }

        $end = $i + 1;

        $str = new static(null);
        $str->codepoints = array_slice($cp, $start, $end - $start);
        $str->length = count($str->codepoints);
        return $str;
    }

    public function indexOf($text, $start = 0)
    {
        if (!$text instanceof static) {
            $text = new UTF8String((string) $text);
        }

        $start = (int) $start;
        $length = $text->length();
        $cp = &$text->getCodepoints();

        for ($i = $start; $i < $this->length; $i++) {
            if ($length > $this->length - $i) {
                return -1;
            }

            $match = true;

            for ($j = 0; $j < $length; $j++) {
                if ($cp[$j] != $this->codepoints[$i + $j]) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return $i;
            }
        }

        return -1;
    }

    public function replace($what, $with)
    {
        $what = (string) $what;
        $with = (string) $with;
        $crt = (string) $this;

        return new static(str_replace($what, $with, $crt));
    }

    public function split($separator = ' ')
    {
        return array_map(function ($value) {
            return new static($value);
        }, explode($separator, (string) $this));
    }

    public function toUpper()
    {
        $cp = array_slice($this->codepoints, 0, $this->length);

        for ($i = 0; $i < $this->length; $i++) {
            $c = &$cp[$i];

            if (($c <= 0x007A && $c >= 0x0061) ||
                ($c <= 0x0FE && $c >= 0x00E0)) {
                $c -= 0x0020;
                continue;
            }

            if ($c <= 0x017E && $c >= 0x0100) {
                if ($c % 2 == 1)
                    $c--;
                continue;
            }

            if ($c <= 0x01DC && $c >= 0x01CD) {
                if ($c % 2 == 0)
                    $c--;
                continue;
            }

            if (($c <= 0x01EF && $c >= 0x01DE) ||
                ($c <= 0x01FF && $c >= 0x01F8) ||
                ($c <= 0x021F && $c >= 0x0200) ||
                ($c <= 0x0233 && $c >= 0x0223) ||
                ($c <= 0x024F && $c >= 0x0246) ||
                ($c <= 0x1E95 && $c >= 0x1E00) ||
                ($c <= 0x1EFF && $c >= 0x1EA0)) {
                if ($c % 2 == 1)
                    $c--;
                continue;
            }

            if ($c <= 0x03CE && $c >= 0x03AC) {
                $c -= 32;
                continue;
            }

            switch ($c) {
                case 0x0183:
                case 0x0185:
                case 0x0188:
                case 0x018C:
                case 0x0192:
                case 0x0199:
                case 0x01A1:
                case 0x01A3:
                case 0x01A5:
                case 0x01A8:
                case 0x01AD:
                case 0x01B0:
                case 0x01B4:
                case 0x01B6:
                case 0x01B9:
                case 0x01BD:
                case 0x01F5:
                case 0x023C:
                case 0x0242:
                //Greek and Coptic
                case 0x0371:
                case 0x0377:
                //Latin extended C
                case 0x2C61:
                    //Latin extended D
                    $c--;
                    continue;
            }

            switch ($c) {
                case 0x01C6:
                case 0x01C9:
                case 0x01CC:
                case 0x01F2:
                    $c -= 2;
            }
        }

        $str = new static(null);
        $str->codepoints = $cp;
        $str->length = $this->length;
        return $str;
    }

    public function toLower()
    {
        $cp = array_slice($this->codepoints, 0, $this->length);

        for ($i = 0; $i < $this->length; $i++) {
            $c = &$cp[$i];

            if (($c <= 0x005A && $c >= 0x0041) ||
                ($c <= 0x0DE && $c >= 0x00C0)) {
                $c += 0x0020;
                continue;
            }

            if ($c <= 0x017E && $c >= 0x0100) {
                if ($c % 2 == 0)
                    $c++;
                continue;
            }

            if ($c <= 0x01DC && $c >= 0x01CD) {
                if ($c % 2 == 1)
                    $c++;
                continue;
            }

            if (($c <= 0x01EF && $c >= 0x01DE) ||
                ($c <= 0x01FF && $c >= 0x01F8) ||
                ($c <= 0x021F && $c >= 0x0200) ||
                ($c <= 0x0233 && $c >= 0x0223) ||
                ($c <= 0x024F && $c >= 0x0246) ||
                ($c <= 0x1E95 && $c >= 0x1E00) ||
                ($c <= 0x1EFF && $c >= 0x1EA0)) {
                if ($c % 2 == 0)
                    $c++;
                continue;
            }

            if ($c <= 0x03A9 && $c >= 0x0391) {
                $c += 32;
                continue;
            }

            switch ($c) {
                case 0x0182:
                case 0x0184:
                case 0x0187:
                case 0x018B:
                case 0x0191:
                case 0x0198:
                case 0x01A0:
                case 0x01A2:
                case 0x01A4:
                case 0x01A7:
                case 0x01AC:
                case 0x019F:
                case 0x01B3:
                case 0x01B5:
                case 0x01B8:
                case 0x01BC:
                case 0x01F4:
                case 0x023B:
                case 0x0241:
                //Greek and Coptic
                case 0x0370:
                case 0x0376:
                //Latin extended C
                case 0x2C60:
                    //Latin extended D
                    $c++;
                    continue;
            }

            switch ($c) {
                case 0x01C4:
                case 0x01C7:
                case 0x01CA:
                case 0x01F0:
                    $c += 2;
            }
        }

        $str = new static(null);
        $str->codepoints = $cp;
        $str->length = $this->length;
        return $str;
    }

    public static function create($bytes)
    {
        return new static((string) $bytes);
    }

    protected static function &getCodes()
    {
        if (static::$codes === null) {
            static::$codes = array();
            $codes = &static::$codes;

            for ($i = 0; $i < 256; $i++) {
                $codes[chr($i)] = $i;
            }
        }

        return static::$codes;
    }
}
