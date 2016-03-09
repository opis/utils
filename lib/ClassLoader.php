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

class ClassLoader
{
    protected $directories;
    protected $aliases = array();
    protected $classes = array();
    protected $namespaces = array();

    public function __construct(array $directories = array(), $preload = false)
    {
        $this->directories = $directories;
        spl_autoload_register(array($this, 'load'), true, $preload);
    }

    public function directory($path)
    {
        $path = rtrim($path);

        if (!in_array($path, $this->directories)) {
            $this->directories[] = $path;
        }
    }

    public function registerNamespace($namespace, $path)
    {
        $this->namespaces[trim($namespace, '\\') . '\\'] = rtrim($path, '/');
    }

    public function alias($alias, $class)
    {
        $this->aliases[$alias] = $class;
    }

    public function aliases(array $aliases)
    {
        $this->aliases = array_merge($this->aliases, $aliases);
    }

    public function mapClass($class, $path)
    {
        $this->classes[$class] = $path;
    }

    public function mapClasses(array $classes)
    {
        $this->classes = array_merge($this->classes, $classes);
    }

    public function load($class)
    {
        $class = ltrim($class, '\\');

        if (isset($this->aliases[$class])) {
            return class_alias($this->aliases[$class], $class);
        } elseif (isset($this->classes[$class])) {
            include $this->classes[$class];
            return true;
        }

        foreach ($this->namespaces as $ns => &$path) {
            if (strpos($class, $ns) === 0 && $this->loadPSR0(substr($class, strlen($ns)), $path)) {
                return true;
            }
        }

        return $this->loadPSR0($class) || $this->loadPSR0(strtolower($class));
    }

    protected function loadPSR0($class, $dir = null)
    {
        $path = '';

        if (($pos = strripos($class, '\\')) !== false) {
            $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
            $class = substr($class, $pos + 1);
        }

        $path .= str_replace('_', '/', $class) . '.php';

        if ($dir === null) {
            foreach ($this->directories as $dir) {
                $dir .= '/' . $path;

                if (file_exists($dir)) {
                    include $dir;
                    return true;
                }
            }
        } else {
            $dir .= '/' . $path;
            if (file_exists($dir)) {
                include $dir;
                return true;
            }
        }
        return false;
    }
}
