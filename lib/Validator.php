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
    protected $errors = array();
    protected $stack = array();
    protected $registred;
    protected $placeholder;

    public function __construct(array $registred = array(), Placeholder $placehodler = null)
    {
        if ($placehodler === null) {
            $placehodler = new Placeholder();
        }
        $this->registred = $registred;
        $this->placeholder = $placehodler;
    }

    protected function push(array $item)
    {
        $this->stack[] = $item;

        return $this;
    }

    public function required($error = '@field is required')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array(),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array(),
                ),
        ));
    }

    public function equalLength($length, $error = '@field must have precisely @length character(s) long')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($length),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@length' => $length),
                ),
        ));
    }

    public function minLength($length, $error = '@field must be at least @length character(s) long')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($length),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@length' => $length),
                ),
        ));
    }

    public function maxLength($length, $error = '@field must be at most @length character(s) long')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($length),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@length' => $length),
                ),
        ));
    }

    public function gt($value, $error = '@field must be greater than @value')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($value),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@value' => $value),
                ),
        ));
    }

    public function gte($value, $error = '@field must be at least @value')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($value),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@value' => $value),
                ),
        ));
    }

    public function lt($value, $error = '@field must be less than @value')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($value),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@value' => $value),
                ),
        ));
    }

    public function lte($value, $error = '@field must be at most @value')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($value),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@value' => $value),
                ),
        ));
    }

    public function equal($value, $error = '@field must be equal with @value')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($value),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@value' => $value),
                ),
        ));
    }

    public function between($min, $max, $error = '@field must be between @min and @max')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($min, $max),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@min' => $min, '@max' => $max),
                ),
        ));
    }

    public function match($value, $field, $error = '@field must match @other')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($value),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array('@other' => $field),
                ),
        ));
    }

    public function email($error = '@field must be a valid email')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array(),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array(),
                ),
        ));
    }

    public function number($error = '@field must be a number')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array(),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array(),
                ),
        ));
    }

    public function regex($pattern, $error = "@field is not valid")
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array($pattern),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array(),
                ),
        ));
    }

    public function fileRequired($error = '@field is required')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array(),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array(),
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
                    'variables' => array(),
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
                    'variables' => array(),
                ),
        ));
    }

    public function register($name, $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('$callback must be a valida callable value');
        }

        $this->registred[$name] = $callback;
    }

    public function __call($name, $arguments)
    {
        if (!isset($this->registred[$name])) {
            throw new Exception(vsprintf("Unknown validator `%s`", array($name)));
        }

        return $this->push(call_user_func_array($this->registred[$name], $arguments));
    }

    public function validate($field, $value)
    {
        while (!empty($this->stack)) {
            $item = array_shift($this->stack);
            $arguments = $item['validator']['arguments'];
            array_unshift($arguments, $value);


            if (false === call_user_func_array($item['validator']['callback'], $arguments)) {
                $error = $item['error'];
                $error['variables']['@field'] = $field;
                $this->errors[] = $this->placeholder->replace($error['text'], $error['variables']);
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
        if (!is_array($value) || !isset($value['name'])) {
            return false;
        }

        return $this->validateRegex($value['name'], $pattern);
    }
}
