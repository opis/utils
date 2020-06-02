<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

class Mutex
{
    /** @var resource File resource */
    protected $fp;

    /** @var string File path */
    protected $file;

    /**
     * Mutex constructor
     *
     * @param string|null $file
     * @param bool $create
     */
    public function __construct(string $file = null, bool $create = false)
    {
        if ($file === null) {
            $file = __FILE__;
        }

        if ($create && !file_exists($file)) {
            file_put_contents($file, '');
        }

        $this->file = $file;
    }

    /**
     * Get the standard mutex implementation
     *
     * @return  Mutex
     */
    public static function standard(): self
    {
        static $instance;

        if ($instance === null) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Acquire the mutex
     *
     * @param bool $wait
     * @return  boolean
     */
    public function lock(bool $wait = true): bool
    {
        if ($this->fp === null) {
            $this->fp = fopen($this->file, 'r');
        }

        return flock($this->fp, $wait ? LOCK_EX : LOCK_EX | LOCK_NB);
    }

    /**
     * Release the mutex
     *
     * @return  boolean
     */
    public function unlock(): bool
    {
        if ($this->fp !== null) {
            flock($this->fp, LOCK_UN);
            fclose($this->fp);
            $this->fp = null;
        }

        return true;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->unlock();
    }
}