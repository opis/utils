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
use InvalidArgumentException;

class Validator
{
    protected static $registred = array();
    protected $errors = array();
    protected $stack = array();
    protected $field;
    
    
    protected function push(array $item)
    {
        $this->stack[] = $item;
        
        return $this;
    }
    
    public function required($error = '%s is required')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array(),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field),
            ),
        ));
    }
    
    public function equalLength($length, $error = '%s must have precisely %s character(s) long')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($length),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $length),
            ),
        ));
    }
    
    public function minLength($length, $error = '%s must be at least %s character(s) long')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($length),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $length),
            ),
        ));
    }
    
    public function maxLength($length, $error = '%s must be at most %s character(s) long')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($length),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $length),
            ),
        ));
    }
    
    public function gt($value, $error = '%s must be greater than %s')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($value),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $value),
            ),
        ));
    }
    
    public function gte($value, $error = '%s must be at least %s')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($value),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $value),
            ),
        ));
    }
    
    public function lt($value, $error = '%s must be less than %s')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($value),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $value),
            ),
        ));
    }
    
    public function lte($value, $error = '%s must be at most %s')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($value),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $value),
            ),
        ));
    }
    
    public function equal($value, $error = '%s must be equal with %s')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($value),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $value),
            ),
        ));
    }
    
    public function between($min, $max, $error = '%s must be between %s and %s')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($min, $max),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $min, $max),
            ),
        ));
    }
    
    public function match($value, $field, $error = '%s must match %s')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($value),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field, $field),
            ),
        ));
    }
    
    public function email($error = '%s must be a valid email')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array(),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field),
            ),
        ));
    }
    
    public function number($error = '%s must be a number')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array(),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field),
            ),
        ));
    }
    
    public function regex($pattern, $error = "%s is not valid")
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($pattern),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field),
            ),
        ));
    }
    
    public function fileRequired($error = '%s is required')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array(),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field),
            ),
        ));
    }
    
    public function fileType($type, $error = 'Invalid file type')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array(),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field),
            ),
        ));
    }
    
    public function fileMatch($pattern, $error = 'Invalid file type')
    {
        return $this->push(array(
            'validator' => array(
                'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                'arguments' => array($pattern),
            ),
            'error' => array(
                'text' => $error,
                'variables' => array(&$this->field),
            ),
        ));
    }
    
    public function &getField()
    {
        return $this->field;
    }
    
    public static function register($name, $callback)
    {
        if(!is_callable($callback))
        {
            throw new InvalidArgumentException('$callback must be a valida callable value');
        }
        
        static::$registred[$name] = $callback;
    }
    
    public function __call($name, $arguments)
    {
        if(!isset(static::$registred[$name]))
        {
            throw new Exception(vsprintf("Unknown validator `%s`", array($name)));
        }
        
        array_unshift($arguments, $this);
        
        return $this->push(call_user_func_array(static::$registred[$name], $arguments));
    }
    
    public function validate($field, $value)
    {
        $this->field = $field;
        
        while(!empty($this->stack))
        {
            $item = array_shift($this->stack);
            $arguments = $item['validator']['arguments'];
            array_unshift($arguments, $value);

            if(false === call_user_func_array($item['validator']['callback'], $arguments))
            {
                $this->errors[] = vsprintf($item['error']['text'], $item['error']['variables']);
                $this->stack = array();
                break;
            }
        }
        
        return $value;
    }
    
    public function hasErrors()
    {
        return !empty($this->errors);
    }
    
    public function errors()
    {
        return $this->errors;
    }
    
    protected function validateRequired($value)
    {
        return $value !== null && $value !== '';
    }
    
    protected function validateMinLength($value, $length)
    {
        return strlen($value) >= $length;
    }
    
    protected function validateMaxLength($value, $length)
    {
        return strlen($value) <= $length;
    }
    
    protected function validateGt($value, $comapare)
    {
        return $value > $comapare;
    }
    
    protected function validateGte($value, $compare)
    {
        return $value >= $compare;
    }
    
    protected function validateLt($value, $comapare)
    {
        return $value < $comapare;
    }
    
    protected function validateLte($value, $compare)
    {
        return $value <= $compare;
    }
    
    protected function validateEqual($value, $compare)
    {
        return $value == $compare;
    }
    
    protected function validateBetween($value, $min, $max)
    {
        return $value >= $min && $value <= $max;
    }
    
    protected function validateMatch($value, $compare)
    {
        return $value == $compare;
    }
    
    protected function validateNumber($value)
    {
        return is_numeric($value);
    }
    
    protected function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    protected function validateRegex($value, $pattern)
    {
        return (bool) preg_match($pattern, $value);
    }
    
    protected function validateFileRequired($value)
    {
        return !(!is_array($value) || !isset($value['name']) || $value['name'] === null || trim($value['name']) === '');
    }
    
    protected function validateFileType($value, $type)
    {
        return is_array($value) && isset($value['type']) && $value['type'] === $type;
    }
    
    protected function validateFileMatch($value, $pattern)
    {
        if(!is_array($value) || !isset($value['name']))
        {
            return false;
        }
        
        return $this->validateRegex($value['name'], $pattern);
    }
}
