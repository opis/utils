<?php
/* ============================================================================
 * Copyright 2020 Zindex Software
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

use Opis\String\UnicodeString;

class Uri
{
    protected const URI_REGEX = '`^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?$`';

    protected const SCHEME_REGEX = '`^[a-z][a-z0-9+-.]*$`i';

    protected const USER_OR_PASS_REGEX = '`^(?:(?:%[a-f0-9]{2})+|[a-z0-9-._~!$&\'()*+,:;=]+)*$`i';

    protected const USERINFO_REGEX = '`^(?<user>[^:]+)(?::(?<pass>.*))?$`';

    protected const HOST_LABEL_REGEX = '`^(?:(?:%[a-f0-9]{2})+|[a-z0-9-._~!$&\'()*+,;=]+)*$`i';

    protected const AUTHORITY_REGEX = '`^(?:(?<userinfo>[^@]+)\@)?(?<host>(\[[a-f0-9:]+\]|[^:]+))(?::(?<port>\d+))?$`i';

    protected const PATH_REGEX = '`^(?:(?:%[a-f0-9]{2})+|[a-z0-9-._~!$&\'()*+,;=:@/]+)*$`i';

    protected const QUERY_OR_FRAGMENT_REGEX = '`^(?:(?:%[a-f0-9]{2})+|[a-z0-9-._~!$&\'()*+,;=:@?/]+)*$`i';

    protected array $components;

    protected ?string $str = null;

    /**
     * @param array $components
     */
    public function __construct(array $components)
    {
        $this->components = $components + [
                'scheme' => null,
                'user' => null,
                'pass' => null,
                'host' => null,
                'port' => null,
                'path' => null,
                'query' => null,
                'fragment' => null,
            ];
    }

    /**
     * @return string|null
     */
    public function scheme(): ?string
    {
        return $this->components['scheme'];
    }

    /**
     * @return string|null
     */
    public function user(): ?string
    {
        return $this->components['user'];
    }

    /**
     * @return string|null
     */
    public function pass(): ?string
    {
        return $this->components['pass'];
    }

    /**
     * @return string|null
     */
    public function userInfo(): ?string
    {
        if ($this->components['user'] === null) {
            return null;
        }

        if ($this->components['pass'] === null) {
            return $this->components['user'];
        }

        return $this->components['user'] . ':' . $this->components['pass'];
    }

    /**
     * @return string|null
     */
    public function host(): ?string
    {
        return $this->components['host'];
    }

    /**
     * @return int|null
     */
    public function port(): ?int
    {
        return $this->components['port'];
    }

    /**
     * @return string|null
     */
    public function authority(): ?string
    {
        if ($this->components['host'] === null) {
            return null;
        }

        $authority = $this->userInfo();
        if ($authority !== null) {
            $authority .= '@';
        }

        $authority .= $this->components['host'];

        if ($this->components['port'] !== null) {
            $authority .= ':' . $this->components['port'];
        }

        return $authority;
    }

    /**
     * @return string|null
     */
    public function path(): ?string
    {
        return $this->components['path'];
    }

    /**
     * @return string|null
     */
    public function query(): ?string
    {
        return $this->components['query'];
    }

    /**
     * @return string|null
     */
    public function fragment(): ?string
    {
        return $this->components['fragment'];
    }

    /**
     * @return array|null[]
     */
    public function components(): array
    {
        return $this->components;
    }

    /**
     * @return bool
     */
    public function isAbsolute(): bool
    {
        return $this->components['scheme'] !== null;
    }

    /**
     * Use this URI as base to resolve the reference
     * @param $ref
     * @return $this|null
     */
    public function resolveRef($ref): ?self
    {
        $ref = self::resolveComponents($ref);
        if ($ref === null) {
            return $this;
        }

        return new static(self::mergeComponents($ref, $this->components));
    }

    /**
     * Resolve this URI reference using a base URI
     * @param static|string|array $base
     * @return static
     */
    public function resolve($base): self
    {
        if ($this->isAbsolute()) {
            return $this;
        }

        $base = self::resolveComponents($base);

        if ($base === null) {
            return $this;
        }

        return new static(self::mergeComponents($this->components, $base));
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if ($this->str !== null) {
            return $this->str;
        }

        $str = '';

        if ($this->components['scheme'] !== null) {
            $str .= $this->components['scheme'] . ':';
        }

        if ($this->components['host'] !== null) {
            $str .= '//' . $this->authority();
        }

        $str .= $this->components['path'];

        if ($this->components['query'] !== null) {
            $str .= '?' . $this->components['query'];
        }

        if ($this->components['fragment'] !== null) {
            $str .= '#' . $this->components['fragment'];
        }

        return $this->str = $str;
    }

    /**
     * @param string $uri
     * @return static|null
     */
    public static function create(string $uri): ?self
    {
        $comp = self::parseComponents($uri);
        return $comp ? new static($comp) : null;
    }

    /**
     * @param string $iri
     * @return string
     */
    public static function percentEncoded(string $iri): string
    {
        $str = '';

        foreach (UnicodeString::getCodePointsFromString($iri) as $code) {
            $code = UnicodeString::getCharFromCodePoint($code);
            $len = strlen($code);
            if ($len === 1) {
                $str .= $code;
                continue;
            }
            for ($i = 0; $i < $len; $i++) {
                $str .= '%' . dechex(ord($code[$i]));
            }
        }

        return $str;
    }

    /**
     * Checks if the scheme contains valid chars
     * @param string $scheme
     * @return bool
     */
    public static function isValidScheme(string $scheme): bool
    {
        return (bool)preg_match(self::SCHEME_REGEX, $scheme);
    }

    /**
     * Checks if user contains valid chars
     * @param string $user
     * @return bool
     */
    public static function isValidUser(string $user): bool
    {
        return (bool)preg_match(self::USER_OR_PASS_REGEX, $user);
    }

    /**
     * Checks if pass contains valid chars
     * @param string $pass
     * @return bool
     */
    public static function isValidPass(string $pass): bool
    {
        return (bool)preg_match(self::USER_OR_PASS_REGEX, $pass);
    }

    /**
     * @param string $userInfo
     * @return bool
     */
    public static function isValidUserInfo(string $userInfo): bool
    {
        /** @var array|string $userInfo */

        if (!preg_match(self::USERINFO_REGEX, $userInfo, $userInfo)) {
            return false;
        }

        if (!self::isValidUser($userInfo['user'])) {
            return false;
        }

        if (isset($userInfo['pass'])) {
            return self::isValidPass($userInfo['pass']);
        }

        return true;
    }

    /**
     * Checks if host is valid
     * @param string $host
     * @return bool
     */
    public static function isValidHost(string $host): bool
    {
        // min and max length
        if ($host === '' || isset($host[253])) {
            return false;
        }

        // check ipv6
        if ($host[0] === '[') {
            if ($host[-1] !== ']') {
                return false;
            }

            return \filter_var(
                    substr($host, 1, -1),
                    \FILTER_VALIDATE_IP,
                    \FILTER_FLAG_IPV6
                ) !== false;
        }

        // check ipv4
        if (is_numeric($host[0])) {
            return \filter_var($host, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false;
        }

        foreach (explode('.', $host) as $host) {
            // empty or too long label
            if ($host === '' || isset($host[63])) {
                return false;
            }
            if (!preg_match(self::HOST_LABEL_REGEX, $host)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the port is valid
     * @param int $port
     * @return bool
     */
    public static function isValidPort(int $port): bool
    {
        return $port >= 0 && $port <= 65535;
    }

    /**
     * Checks if authority contains valid chars
     * @param string $authority
     * @return bool
     */
    public static function isValidAuthority(string $authority): bool
    {
        if ($authority === '') {
            return true;
        }

        /** @var array|string $authority */

        if (!preg_match(self::AUTHORITY_REGEX, $authority, $authority)) {
            return false;
        }

        if (isset($authority['port']) && !self::isValidPort((int)$authority['port'])) {
            return false;
        }

        if (isset($authority['userinfo']) && !self::isValidUserInfo($authority['userinfo'])) {
            return false;
        }

        return self::isValidHost($authority['host']);
    }

    /**
     * Checks if the path contains valid chars
     * @param string $path
     * @return bool
     */
    public static function isValidPath(string $path): bool
    {
        return $path === '' || (bool)preg_match(self::PATH_REGEX, $path);
    }

    /**
     * Checks if the query string contains valid chars
     * @param string $query
     * @return bool
     */
    public static function isValidQuery(string $query): bool
    {
        return $query === '' || (bool)preg_match(self::QUERY_OR_FRAGMENT_REGEX, $query);
    }

    /**
     * Checks if the fragment contains valid chars
     * @param string $fragment
     * @return bool
     */
    public static function isValidFragment(string $fragment): bool
    {
        return $fragment === '' || (bool)preg_match(self::QUERY_OR_FRAGMENT_REGEX, $fragment);
    }

    /**
     * Removes dot segments from path
     * @param string $path
     * @return string
     */
    public static function removeDotSegmentsFromPath(string $path): string
    {
        // Fast check common simple paths
        if ($path === '' || $path === '/') {
            return $path;
        }

        $output = '';
        $last_slash = 0;

        $len = strlen($path);
        $i = 0;

        while ($i < $len) {
            if ($path[$i] === '.') {
                $j = $i + 1;
                // search for .
                if ($j >= $len) {
                    break;
                }

                // search for ./
                if ($path[$j] === '/') {
                    $i = $j + 1;
                    continue;
                }

                // search for ../
                if ($path[$j] === '.') {
                    $k = $j + 1;
                    if ($k >= $len) {
                        break;
                    }
                    if ($path[$k] === '/') {
                        $i = $k + 1;
                        continue;
                    }
                }
            } elseif ($path[$i] === '/') {
                $j = $i + 1;
                if ($j >= $len) {
                    $output .= '/';
                    break;
                }

                // search for /.
                if ($path[$j] === '.') {
                    $k = $j + 1;
                    if ($k >= $len) {
                        $output .= '/';
                        break;
                    }
                    // search for /./
                    if ($path[$k] === '/') {
                        $i = $k;
                        continue;
                    }
                    // search for /..
                    if ($path[$k] === '.') {
                        $n = $k + 1;
                        if ($n >= $len) {
                            // keep the slash
                            $output = substr($output, 0, $last_slash + 1);
                            break;
                        }
                        // search for /../
                        if ($path[$n] === '/') {
                            $output = substr($output, 0, $last_slash);
                            $last_slash = (int)strrpos($output, '/');
                            $i = $n;
                            continue;
                        }
                    }
                }
            }

            $pos = strpos($path, '/', $i + 1);

            if ($pos === false) {
                $output .= substr($path, $i);
                break;
            }

            $last_slash = strlen($output);
            $output .= substr($path, $i, $pos - $i);

            $i = $pos;
        }

        return $output;
    }

    /**
     * @param string $uri
     * @param bool $expand_authority
     * @param bool $validate
     * @return array|null
     */
    public static function parseComponents(string $uri, bool $expand_authority = true, bool $validate = true): ?array
    {
        if (!preg_match(self::URI_REGEX, $uri, $uri)) {
            return null;
        }

        $comp = [];

        // scheme
        if (isset($uri[2]) && $uri[2] !== '') {
            if ($validate && !self::isValidScheme($uri[2])) {
                return null;
            }
            $comp['scheme'] = $uri[2];
        }

        // authority
        if (isset($uri[4]) && isset($uri[3][0])) {
            if ($uri[4] === '') {
                if ($expand_authority) {
                    $comp['host'] = '';
                } else {
                    $comp['authority'] = '';
                }
            } elseif ($expand_authority) {
                $au = self::parseAuthorityComponents($uri[4], $validate);
                if ($au === null) {
                    return null;
                }
                $comp += $au;
                unset($au);
            } else {
                if ($validate && !self::isValidAuthority($uri[4])) {
                    return null;
                }
                $comp['authority'] = $uri[4];
            }
        }

        // path
        if (isset($uri[5])) {
            if ($validate && !self::isValidPath($uri[5])) {
                return null;
            }
            $comp['path'] = $uri[5];
            // not a relative uri, remove dot segments
            if (isset($comp['scheme']) || isset($comp['authority']) || isset($comp['host'])) {
                $comp['path'] = self::removeDotSegmentsFromPath($comp['path']);
            }
        }

        // query
        if (isset($uri[7]) && isset($uri[6][0])) {
            if ($validate && !self::isValidQuery($uri[7])) {
                return null;
            }
            $comp['query'] = $uri[7];
        }

        // fragment
        if (isset($uri[9]) && isset($uri[8][0])) {
            if ($validate && !self::isValidFragment($uri[9])) {
                return null;
            }
            $comp['fragment'] = $uri[9];
        }

        return $comp;
    }

    /**
     * @param self|string|array $uri
     * @return array|null
     */
    public static function resolveComponents($uri): ?array
    {
        if ($uri instanceof self) {
            return $uri->components;
        }

        if (is_string($uri)) {
            return self::parseComponents($uri);
        }

        if (is_array($uri)) {
            if (isset($uri['host'])) {
                unset($uri['authority']);
            } elseif (isset($uri['authority'])) {
                $au = self::parseAuthorityComponents($uri['authority']);
                unset($uri['authority']);
                if ($au !== null) {
                    unset($uri['user'], $uri['pass'], $uri['host'], $uri['port']);
                    $uri += $au;
                }
            }
            return $uri;
        }

        return null;
    }

    /**
     * @param string $authority
     * @param bool $validate
     * @return array|null
     */
    public static function parseAuthorityComponents(string $authority, bool $validate = true): ?array
    {
        /** @var array|string $authority */

        if (!preg_match(self::AUTHORITY_REGEX, $authority, $authority)) {
            return null;
        }

        $comp = [];

        // userinfo
        if (isset($authority['userinfo']) && $authority['userinfo'] !== '') {
            if (!preg_match(self::USERINFO_REGEX, $authority['userinfo'], $ui)) {
                return null;
            }

            // user
            if ($validate && !self::isValidUser($ui['user'])) {
                return null;
            }
            $comp['user'] = $ui['user'];

            // pass
            if (isset($ui['pass']) && $ui['pass'] !== '') {
                if ($validate && !self::isValidPass($ui['pass'])) {
                    return null;
                }
                $comp['pass'] = $ui['pass'];
            }

            unset($ui);
        }

        // host
        if ($validate && !self::isValidHost($authority['host'])) {
            return null;
        }
        $comp['host'] = $authority['host'];


        // port
        if (isset($authority['port'])) {
            $authority['port'] = (int)$authority['port'];
            if (!self::isValidPort($authority['port'])) {
                return null;
            }
            $comp['port'] = $authority['port'];
        }

        return $comp;
    }

    /**
     * @param array $ref
     * @param array $base
     * @return array
     */
    public static function mergeComponents(array $ref, array $base): array
    {
        if (isset($ref['scheme'])) {
            $dest = $ref;
        } else {
            $dest = [];

            $dest['scheme'] = $base['scheme'] ?? null;

            if (isset($ref['authority']) || isset($ref['host'])) {
                $dest += $ref;
            } else {
                if (isset($base['authority'])) {
                    $dest['authority'] = $base['authority'];
                } else {
                    $dest['user'] = $base['user'] ?? null;
                    $dest['pass'] = $base['pass'] ?? null;
                    $dest['host'] = $base['host'] ?? null;
                    $dest['port'] = $base['port'] ?? null;
                }

                if (!isset($ref['path'])) {
                    $ref['path'] = '';
                }
                if (!isset($base['path'])) {
                    $base['path'] = '';
                }

                if ($ref['path'] === '') {
                    $dest['path'] = $base['path'];
                    $dest['query'] = $ref['query'] ?? $base['query'] ?? null;
                } else {
                    if ($ref['path'][0] === '/') {
                        $dest['path'] = $ref['path'];
                    } else {
                        if ((isset($base['authority']) || isset($base['host'])) && $base['path'] === '') {
                            $dest['path'] = '/' . $ref['path'];
                        } else {
                            $dest['path'] = $base['path'];

                            if ($dest['path'] !== '') {
                                $pos = strrpos($dest['path'], '/');
                                if ($pos === false) {
                                    $dest['path'] = '';
                                } else {
                                    $dest['path'] = substr($dest['path'], 0, $pos);
                                }

                                unset($pos);
                            }
                            $dest['path'] .= '/' . $ref['path'];
                        }
                    }

                    $dest['query'] = $ref['query'] ?? null;
                }
            }
        }

        if (isset($dest['path'])) {
            $dest['path'] = self::removeDotSegmentsFromPath($dest['path']);
        }

        $dest['fragment'] = $ref['fragment'] ?? null;

        return $dest;
    }
}