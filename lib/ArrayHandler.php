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
    /** @var    array   The array */
    protected $item;
    
    /**
     * Constructor
     *
     * @access  public
     *
     * @param   array   $array  (optional) The array
     */
    
    public function __construct(array $array = array())
    {
        $this->item = $array;
    }
    
    /**
     * Get the value stored under the specified path
     *
     * @access  public
     *
     * @param   string  $path       Value's path
     * @param   mixed   $default    Default value that will be returned
     *
     * @return  mixed
     */
    
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
        
        return isset($item[$last]) ? $item[$last] : $default;
    }
    
    /**
     * Check if a path exists
     *
     * @access  public
     *
     * @param   string  $path   Path to check
     *
     * @return  boolean
     */
    
    public function has($path)
    {
        return $this !== $this->get($path, $this);
    }
    
    /**
     * Store a value
     *
     * @access  public
     *
     * @param   string  $path   Where to store
     * @param   mixed   $value  The value being stored
     */
    
    public function put($path, $value)
    {
        $value = json_decode(json_encode($value), true);
        $path = explode('.', $path);
        $last = array_pop($path);
        $item = &$this->item;
        
        foreach($path as $key)
        {
            if(!isset($item[$key]) || !is_array($item[$key]))
            {
                $item[$key] = array();
            }
            
            $item = &$item[$key];
        }
        
        $item[$last] = $value;
        
    }
    
    /**
     * Check if the value stored under the specified path is a JSON array
     *
     * @access  public
     *
     * @param   string  $path   The path to check
     *
     * @return  boolean
     */
    
    public function isArray($path)
    {
        $value = $this->get($path, $this);
        
        if($value === $this || !is_array($value))
        {
            return false;
        }
        
        return array_keys($value) === range(0, count($value) - 1);
    }
    
    /**
     * Check if the value stored under the specified path is a JSON object
     *
     * @access  public
     *
     * @param   string  $path   The path to check
     *
     * @return  boolean
     */
    
    public function isObject($path)
    {
        $value = $this->get($path, $this);
        
        if($value === $this || !is_array($value))
        {
            return false;
        }
        
        return array_keys($value) !== range(0, count($value) - 1);
    }
    
    /**
     * Check if the value stored under the specified path is a string
     *
     * @access  public
     *
     * @param   string  $path   The path to check
     *
     * @return  boolean
     */
    
    public function isString($path)
    {
        $value = $this->get($path, $this);
        
        return $value === $this ? false : is_string($value);
    }
    
    /**
     * Check if the value stored under the specified path is a number
     *
     * @access  public
     *
     * @param   string  $path   The path to check
     *
     * @return  boolean
     */
    
    public function isNumber($path)
    {
        $value = $this->get($path, $this);
        
        return $value === $this ? false : is_numeric($value);
    }
    
    /**
     * Check if the value stored under the specified path is a `null` value
     *
     * @access  public
     *
     * @param   string  $path   The path to check
     *
     * @return  boolean
     */
    
    public function isNull($path)
    {
        $value = $this->get($path, $this);
        
        return $value === $this ? false : is_null($value);
    }
    
    /**
     * Check if the value stored under the specified path is a boolean value
     *
     * @access  public
     *
     * @param   string  $path   The path to check
     *
     * @return  boolean
     */
    
    public function isBoolean($path)
    {
        $value = $this->get($path, $this);
        
        return $value === $this ? false : is_bool($value);
    }
    
    /**
     * Get the current array
     *
     * @return  array
     */
    
    public function toArray()
    {
        return $this->item;
    }
    
    /**
     * Get the JSON representation of the current array
     *
     * @access  public
     *
     * @return  string
     */
    
    public function toJSON()
    {
        return json_encode($this->toArray());
    }
    
}
