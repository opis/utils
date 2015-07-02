<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2015 Marius Sarca
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

class Punycode
{

    /**
     * Bootstring parameter values
     *
     */
    
    const BASE         = 36;
    const TMIN         = 1;
    const TMAX         = 26;
    const SKEW         = 38;
    const DAMP         = 700;
    const INITIAL_BIAS = 72;
    const INITIAL_N    = 128;
    const PREFIX       = 'xn--';
    const DELIMITER    = '-';
    
    /**
     * Encode table
     *
     * @param array
     */
    
    protected static $encodeTable = array(
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
        'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
        'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    );

    /**
     * Decode table
     *
     * @param array
     */
    
    protected static $decodeTable = array(
        'a' =>  0, 'b' =>  1, 'c' =>  2, 'd' =>  3, 'e' =>  4, 'f' =>  5,
        'g' =>  6, 'h' =>  7, 'i' =>  8, 'j' =>  9, 'k' => 10, 'l' => 11,
        'm' => 12, 'n' => 13, 'o' => 14, 'p' => 15, 'q' => 16, 'r' => 17,
        's' => 18, 't' => 19, 'u' => 20, 'v' => 21, 'w' => 22, 'x' => 23,
        'y' => 24, 'z' => 25, '0' => 26, '1' => 27, '2' => 28, '3' => 29,
        '4' => 30, '5' => 31, '6' => 32, '7' => 33, '8' => 34, '9' => 35
    );

    /**
     * Encode a domain to its Punycode version
      *
     * @param string $input Domain name in Unicde to be encoded
     * @return string Punycode representation in ASCII
     */
    
    public static function encode($input)
    {
        $parts = explode('.', $input);
        
        foreach ($parts as &$part)
        {
            $part = static::encodePart($part);
        }
        
        return implode('.', $parts);
    }

    /**
     * Encode a part of a domain name, such as tld, to its Punycode version
     *
     * @param string $input Part of a domain name
     * @return string Punycode representation of a domain part
     */
    
    protected static function encodePart(&$input)
    {
        $codePoints = &static::codePoints($input);
        
        $n = static::INITIAL_N;
        $bias = static::INITIAL_BIAS;
        $delta = 0;
        $h = $b = count($codePoints['basic']);
        
        $output = static::getString($codePoints['basic']);
        
        if ($input === $output)
        {
            return $output;
        }
        
        if ($b > 0)
        {
            $output .= static::DELIMITER;
        }
        
        $codePoints['nonBasic'] = array_unique($codePoints['nonBasic']);
        sort($codePoints['nonBasic']);
        
        $i = 0;
        $length = $codePoints['length'];
        
        while ($h < $length)
        {
            $m = $codePoints['nonBasic'][$i++];
            $delta = $delta + ($m - $n) * ($h + 1);
            $n = $m;
            
            foreach ($codePoints['all'] as $c)
            {
                if ($c < $n || $c < static::INITIAL_N)
                {
                    $delta++;
                }
                if ($c === $n)
                {
                    $q = $delta;
                    for ($k = static::BASE;; $k += static::BASE)
                    {
                        $t = static::calculateThreshold($k, $bias);
                        
                        if ($q < $t)
                        {
                            break;
                        }
                        
                        $code = $t + (($q - $t) % (static::BASE - $t));
                        $output .= static::$encodeTable[$code];
                        
                        $q = ($q - $t) / (static::BASE - $t);
                    }
                    
                    $output .= static::$encodeTable[$q];
                    $bias = static::adapt($delta, $h + 1, ($h === $b));
                    $delta = 0;
                    $h++;
                }
            }
            
            $delta++;
            $n++;
        }
        
        return static::PREFIX . $output;
    }

    /**
     * Decode a Punycode domain name to its Unicode counterpart
     *
     * @param string $input Domain name in Punycode
     * @return string Unicode domain name
     */
    
    public static function decode($input)
    {
        $parts = explode('.', $input);
        
        foreach ($parts as &$part)
        {
            if (strpos($part, static::PREFIX) !== 0)
            {
                continue;
            }
            
            $part = substr($part, strlen(static::PREFIX));
            $part = static::decodePart($part);
        }
        
        return implode('.', $parts);
    }

    /**
     * Decode a part of domain name, such as tld
     *
     * @param string $input Part of a domain name
     * @return string Unicode domain part
     */
    
    protected static function decodePart($input)
    {
        $n = static::INITIAL_N;
        $i = 0;
        $bias = static::INITIAL_BIAS;
        $output = '';
        
        $pos = strrpos($input, static::DELIMITER);
        
        if ($pos !== false)
        {
            $output = substr($input, 0, $pos++);
        }
        else
        {
            $pos = 0;
        }
        
        $outputLength = strlen($output);
        $inputLength = strlen($input);
        $res = array();
        
        while ($pos < $inputLength)
        {
            $oldi = $i;
            $w = 1;
            
            for ($k = static::BASE;; $k += static::BASE)
            {
                $digit = static::$decodeTable[$input[$pos++]];
                $i = $i + ($digit * $w);
                $t = static::calculateThreshold($k, $bias);
                
                if ($digit < $t)
                {
                    break;
                }
                
                $w = $w * (static::BASE - $t);
            }
            
            $bias = static::adapt($i - $oldi, ++$outputLength, ($oldi === 0));
            $n = $n + (int) ($i / $outputLength);
            $i = $i % ($outputLength);
            
            if($n < 0x80)
            {
                $cp = chr($n);
            }
            elseif($n <= 0x7FF)
            {
                $cp = chr(($n >> 6) + 0xC0) . chr(($n & 0x3F) + 0x80);
            }
            elseif($n <= 0xFFFF)
            {
                $cp = chr(($n >> 12) + 0xE0) . chr((($n >> 6) & 0x3F) + 0x80) . chr(($n & 0x3F) + 0x80);
            }
            elseif($n <= 0x10FFFF)
            {
                $cp = chr(($n >> 18) + 0xF0) . $hr((($n >> 12) & 0x3F) + 0x80)
                    . chr((($n >> 6) & 0x3F) + 0x80) . chr(($n & 0x3F) + 0x80);
            }
            else
            {
                throw new Exception("Invalid UTF-8");
            }
            
            $output = substr($output, 0, $i) . $cp . substr($output, $i, $outputLength - 1);
            
            $i += strlen($cp);
        }
        
        return $output;
    }

    /**
     * Calculate the bias threshold to fall between TMIN and TMAX
     *
     * @param integer $k
     * @param integer $bias
     * @return integer
     */
    
    protected static function calculateThreshold($k, $bias)
    {
        if ($k <= $bias + static::TMIN)
        {
            return static::TMIN;
        }
        elseif ($k >= $bias + static::TMAX)
        {
            return static::TMAX;
        }
        
        return $k - $bias;
    }

    /**
     * Bias adaptation
     *
     * @param integer $delta
     * @param integer $numPoints
     * @param boolean $firstTime
     * @return integer
     */
    
    protected static function adapt($delta, $numPoints, $firstTime)
    {
        $delta = (int) (
            ($firstTime)
            ? $delta / static::DAMP
            : $delta / 2
        );
        
        $delta += (int) ($delta / $numPoints);
        
        $k = 0;
        while ($delta > ((static::BASE - static::TMIN) * static::TMAX) / 2)
        {
            $delta = (int) ($delta / (static::BASE - static::TMIN));
            $k = $k + static::BASE;
        }
        
        $k = $k + (int) (((static::BASE - static::TMIN + 1) * $delta) / ($delta + static::SKEW));
        
        return $k;
    }

    /**
     * List code points for a given input
     *
     * @param string $input
     * @return array Multi-dimension array with basic, non-basic and aggregated code points
     */
    
    protected static function &codePoints($input)
    {
        $codePoints = array(
            'all'      => array(),
            'basic'    => array(),
            'nonBasic' => array(),
        );
        
        $codes = &static::getCharsCodes($input);
        $codePoints['length'] = $length = count($codes);
        
        for ($i = 0; $i < $length; $i++)
        {
            $code = $codes[$i];
            
            if ($code < 128)
            {
                $codePoints['all'][] = $codePoints['basic'][] = $code;
            }
            else
            {
                $codePoints['all'][] = $codePoints['nonBasic'][] = $code;
            }
        }
        
        return $codePoints;
    }

    protected static function &getCharsCodes(&$bytes)
    {
        static $codes = null;
        
        if($codes === null)
        {
            $codes = array();
            
            for($i = 0; $i < 256; $i++)
            {
                $codes[chr($i)] = $i;
            }
        }
        
        $utf8 = array();
        $l = strlen($bytes);
        $p = 0;
        $hasErrors = false;
        
        do
        {
            $cu1 = $codes[$bytes[$p++]];
            
            if($cu1 < 0x80)
            {
                $utf8[] = $cu1;
            }
            elseif($cu1 < 0xC2)
            {
                $utf8[] = 0xFFFD;
                $hasErrors = true;
            }
            elseif($cu1 < 0xE0)
            {
                $cu2 = $codes[$bytes[$p++]];
                
                if(($cu2 & 0xC0) != 0x80)
                {
                    $p--;
                    $utf8[] = 0xFFFD;
                    $hasErrors = true;
                    continue;
                }
                
                $utf8[] = ($cu1 << 6) + $cu2 - 0x3080;
            }
            elseif($cu1 < 0xF0)
            {
                $cu2 = $codes[$bytes[$p++]];
                
                if((($cu2 & 0xC0) != 0x80) || ($cu1 == 0xE0 && $cu2 < 0xA0))
                {
                    $p--;
                    $utf8[] = 0xFFFD;
                    $hasErrors = true;
                    continue;
                }
                
                $cu3 = $codes[$bytes[$p++]];
                
                if(($cu3 & 0xC0) != 0x80)
                {
                    $p -= 2;
                    $utf8[] = 0xFFFD;
                    $hasErrors = true;
                    continue;
                }
                
                $utf8[] = ($cu1 << 12) + ($cu2 << 6) + $cu3 - 0xE2080;
            
            }
            elseif($cu1 < 0xF5)
            {
                $cu2 = $codes[$bytes[$p++]];
                
                if((($cu2 & 0xC0) != 0x80) || ($cu1 == 0xF0 && $cu2 < 0x90) || ($cu1 == 0xF4 && $cu2 >= 0x90))
                {
                    $p--;
                    $utf8[] = 0xFFFD;
                    $hasErrors = true;
                    continue;
                }
                
                $cu3 = $codes[$bytes[$p++]];
                
                if(($cu3 & 0xC0) != 0x80)
                {
                    $p -= 2;
                    $utf8[] = 0xFFFD;
                    $hasErrors = true;
                    continue;
                }
                
                $cu4 = $codes[$bytes[$p++]];
                
                if(($cu4 & 0xC0) != 0x80)
                {
                    $p -= 3;
                    $utf8[] = 0xFFFD;
                    $hasErrors = true;
                    continue;
                }
                
                $utf8[] = ($cu1 << 18) + ($cu2 << 12) + ($cu3 << 6) + $cu4 - 0x3C82080;
            
            }
            else
            {
                $utf8[] = 0xFFFD;
                $hasErrors = true;
            }
        
        }
        while($p < $l);
        
        return $utf8;
    }

    protected static function getString(&$charpoints)
    {
        $output = '';
        
        foreach($charpoints as &$cp)
        {
        
            if($cp < 0x80)
            {
                $output .= chr($cp);
            }
            elseif($cp <= 0x7FF)
            {
                $output .= chr(($cp >> 6) + 0xC0) . chr(($cp & 0x3F) + 0x80);
            }
            elseif($cp <= 0xFFFF)
            {
                $output .= chr(($cp >> 12) + 0xE0) . chr((($cp >> 6) & 0x3F) + 0x80) . chr(($cp & 0x3F) + 0x80);
            }
            elseif($cp <= 0x10FFFF)
            {
                $output .= chr(($cp >> 18) + 0xF0) . $hr((($cp >> 12) & 0x3F) + 0x80)
                        . chr((($cp >> 6) & 0x3F) + 0x80) . chr(($cp & 0x3F) + 0x80);
            }
            else
            {
                throw new Exception("Invalid UTF-8");
            }
        }
        
        return $output;
    }
}
