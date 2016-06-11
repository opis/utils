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

class Placeholder
{
    /** @var    string */
    protected $escape;

    /** @var    string */
    protected $plain;

    /**
     * Constructor
     * 
     * @param   string  $escape
     * @param   string  $plain
     */
    public function __construct($escape = '@', $plain = '%')
    {
        $this->escape = $escape;
        $this->plain = $plain;
    }

    /**
     * Replace placeholders
     * 
     * @param   string    $text
     * @param   array   $placeholders
     * @param   boolean $escape         (optional)
     * 
     * @return  string
     */
    public function replace($text, array $placeholders, $escape = true)
    {
        $args = array();
        
        foreach ($placeholders as $key => $value) {
            $l = $key[0];
            if ($l == $this->escape) {
                $args[$key] = $escape ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            } elseif ($l == $this->plain) {
                $args[$key] = $value;
            }
        }

        return strtr($text, $args);
    }
}
