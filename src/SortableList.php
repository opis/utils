<?php
/* ===========================================================================
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

class SortableList
{
    private array $entries = [];
    private bool $sorted = true;
    private bool $descending = true;

    public function addItem($item, int $priority = 0): void
    {
        $this->entries[] = [$priority, $item];
        $this->sorted = false;
    }

    public function sort(): void
    {
        if ($this->sorted) {
            return;
        }

        $descending = $this->descending;
        $values = array_reverse($this->entries);

        $done = false;

        while (!$done) {
            $done = true;
            for ($i = 0, $l = count($this->entries) - 1; $i < $l; $i++) {

                if ($descending) {
                    $invert = $values[$i][0] < $values[$i + 1][0];
                } else {
                    $invert = $values[$i][0] > $values[$i + 1][0];
                }

                if ($invert) {
                    $done = false;
                    $tmp = $values[$i];
                    $values[$i] = $values[$i + 1];
                    $values[$i + 1] = $tmp;
                }
            }
        }

        $this->entries = $values;
        $this->sorted = true;
    }

    public function isSorted(): bool
    {
        return $this->sorted;
    }

    public function getValues(bool $sort = true): \Generator
    {
        if ($sort) {
            $this->sort();
        }
        foreach ($this->entries as $entry) {
            yield $entry[1];
        }
    }

    public function __serialize(): array
    {
        return [
            'entries' => $this->entries,
            'sorted' => $this->sorted,
            'descending' => $this->descending,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->entries = $data['entries'];
        $this->sorted = $data['sorted'];
        $this->descending = $data['descending'];
    }
}