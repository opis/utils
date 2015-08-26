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

class ArrayHandler
{
    protected $item;
    
    public function __construct(array $array = array())
    {
        $this->item = $array;
    }
    
    public function get($path, $default = null)
    {
        $path = explode('.', $path);
        $last = array_pop($path);
        
        $item = &$this->item;
        
        foreach($path as $key)
        {
            if(isset($item[$key]))
            {
                $item = &$item[$key];
                continue;
            }
            
            return $default;
        }
        
        return isset($item[$last]) ?: $default;
    }
    
    public function has($path)
    {
        return $this !== $this->get($path, $this);
    }
    
    public function put($path, $value)
    {
        $path = explode('.', $path);
        $item = 
        
    }
    
}
