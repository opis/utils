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

class Url
{
    const URI_REGEX = '`^((?P<scheme>[^:/?#]+):)?(?P<authority>//([^/?#]*))?(?P<path>[^?#]*)(?P<query>\?([^#]*))?(?P<fragment>#(.*))?`';
    const AUTHORITY_REGEX = '`^//((?P<host>[^/?#:]+))?(:(?P<port>[0-9]+))?`';

    protected static $empty = array(
        'scheme' => '',
        'authority' => '',
        'path' => '',
        'query' => '',
        'fragment' => '',
    );
    protected $url;
    protected $components;
    protected $authority;

    /**
     * Url constructor.
     * @param array $components
     */
    protected function __construct(array $components)
    {
        $this->components = $components;
    }

    /**
     * @return array
     */
    protected function &getAuthority()
    {
        if ($this->authority === null) {
            $this->authority = array();
            $result = array();

            if (preg_match(static::AUTHORITY_REGEX, $this->components['authority'], $result)) {
                if (isset($result['host'])) {
                    $this->authority['host'] = $result['host'];
                }

                if (isset($result['port'])) {
                    $this->authority['port'] = $result['port'];
                }
            }
        }

        return $this->authority;
    }

    /**
     * @param $value
     * @return mixed|null
     */
    public function __get($value)
    {
        if (isset($this->components[$value])) {
            return $this->components[$value];
        } else {
            $authority = &$this->getAuthority();

            if (isset($authority[$value])) {
                return $authority[$value];
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->url === null) {
            $c = $this->components;

            if ($c['authority'] && null !== $host = $this->host) {
                $port = $this->port;
                $authority = '//' . Punycode::encode($host) . ($port === null ? '' : ':' . $port);
                $c['authority'] = $authority;
            }

            $this->url = implode('', $c);
        }

        return $this->url;
    }

    /**
     * @param Url $base
     * @param string $path
     * @return string
     */
    protected static function merge(Url $base, $path)
    {
        if (!$base->components['path']) {
            return '/' . $base;
        }

        $paths = explode('/', $base->components['path']);
        $paths[count($paths) - 1] = $path;
        return implode('/', $paths);
    }

    /**
     * @param string $path
     * @return string
     */
    protected static function removeDotSegments($path)
    {
        $stack = array();
        $input = explode('/', $path);

        foreach ($input as $i) {
            switch ($i) {
                case '.':
                    continue;
                case '..':
                    array_pop($stack);
                    break;
                default:
                    array_push($stack, $i);
            }
        }

        return implode('/', $stack);
    }

    /**
     * @param string $url
     * @return array
     */
    public static function components($url)
    {
        preg_match(static::URI_REGEX, $url, $match);

        $components = array();
        
        foreach ($match as $key => $value) {
            if (!is_integer($key)) {
                $components[$key] = $value;
            }
        }

        $components += static::$empty;

        return $components;
    }

    /**
     * @param string $url
     * @return static
     */
    public static function parse($url)
    {
        if ($url instanceof Url) {
            return $url;
        }

        return new static(static::components($url));
    }

    /**
     * @param string $base
     * @param string $relative
     * @return static
     */
    public static function compose($base, $relative)
    {
        $b = static::parse($base);
        $r = static::parse($relative);
        $t = new static(static::$empty);

        if ($b->components['scheme'] === $r->components['scheme']) {
            $r->components['scheme'] = '';
        }

        if ($r->components['scheme']) {
            $t->components['scheme'] = $r->components['scheme'];
            $t->components['authority'] = $r->components['authority'];
            $t->components['path'] = static::removeDotSegments($r->components['path']);
            $t->components['query'] = $r->components['query'];
        } else {
            if ($r->components['authority']) {
                $t->components['authority'] = $r->components['authority'];
                $t->components['path'] = static::removeDotSegments($r->components['path']);
                $t->components['query'] = $r->components['query'];
            } else {
                if (!$r->components['path']) {
                    $t->components['path'] = $b->components['path'];

                    if ($r->components['query']) {
                        $t->components['query'] = $r->components['query'];
                    } else {
                        $t->components['query'] = $b->components['query'];
                    }
                } else {
                    if ($r->components['path'][0] == '/') {
                        $t->components['path'] = static::removeDotSegments($r->components['path']);
                    } else {
                        $t->components['path'] = static::merge($b, $r->components['path']);
                        $t->components['path'] = static::removeDotSegments($t->components['path']);
                    }

                    $t->components['query'] = $r->components['query'];
                }

                $t->components['authority'] = $b->components['authority'];
            }

            $t->components['scheme'] = $b->components['scheme'];
        }

        $t->components['fragment'] = $r->components['fragment'];

        return $t;
    }
}
