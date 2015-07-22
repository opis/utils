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


class ClassLoader
{
    protected static $aliases = array();
    
    protected static $classes = array();
    
    protected static $directories;
    
    protected static $namespaces = array();

    protected function __construct()
    {
        
    }

    public static function directory($path)
    {
        $path = rtrim($path);
        if (!in_array($path, self::$directories))
        {
            self::$directories[] = $path;
        }
    }

    public static function registerNamespace($namespace, $path)
    {
        self::$namespaces[trim($namespace, '\\') . '\\'] = rtrim($path, '/');
    }

    public static function alias($alias, $class)
    {
        self::$aliases[$alias] = $class;
    }
    
    public static function aliases(array $aliases)
    {
        self::$aliases = array_merge(self::$aliases, $aliases);
    }

    public static function mapClass($class, $path)
    {
        self::$classes[$class] = $path;
    }

    public static function mapClasses(array $classes)
    {
        self::$classes = array_merge(self::$classes, $classes);
    }

    public static function init(array $dirs = array(), $preload = false)
    {
        self::$directories = $dirs;
        spl_autoload_register(__CLASS__ . '::load', true, $preload);
    }

    public static function load($class)
    {
        $class = ltrim($class, '\\');
        
        if (isset(self::$aliases[$class]))
        {
            return class_alias(self::$aliases[$class], $class);
        }
        elseif(isset(self::$classes[$class]))
        {
            include self::$classes[$class];
            return TRUE;
        }

        foreach(self::$namespaces as $ns => &$path)
        {
            if(strpos($class, $ns) === 0 && self::loadPSR0(substr($class, strlen($ns)), $path))
            {
                return true;
            }
        }
        
        return self::loadPSR0($class) || self::loadPSR0(strtolower($class));
    }
    

    protected static function loadPSR0($class, $dir = NULL)
    {
        $path = '';

        if(($pos = strripos($class, '\\')) !== FALSE)
        {
            $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
            $class = substr($class, $pos + 1);
        }

        $path .= str_replace('_', '/', $class) . '.php';
        
        if ($dir === NULL)
        {
            foreach (self::$directories as $dir)
            {
                $dir .= '/' . $path;
                
                if (file_exists($dir))
                {
                    include $dir;
                    return TRUE;
                }
            }
        }
        else
        {
            $dir .= '/' . $path;
            if (file_exists($dir))
            {
                include $dir;
                return TRUE;
            }
        }
        
        return FALSE;
    }
}
