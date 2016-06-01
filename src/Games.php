<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Cudev Ltd.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace GameFeed;

use AppendIterator;
use Countable;
use Iterator;

class Games implements Iterator, Countable
{
    private $items = [];
    private $inner;
    private $position = 0;
    private $adapters = [];

    /**
     * @param RetrieverInterface[] ...$adapters
     * @return static
     */
    public static function from(RetrieverInterface ...$adapters)
    {
        return new static(...$adapters);
    }

    public function __construct(RetrieverInterface ...$adapters)
    {
        $this->adapters = $adapters;
        $this->inner = new AppendIterator();

        foreach ($adapters as $adapter) {
            $this->inner->append($adapter->retrieve());
        }
    }

    public function current()
    {
        if (!array_key_exists($this->position, $this->items)) {
            $this->items[$this->position] = $this->inner->current();
        }
        return $this->items[$this->position];
    }

    public function next()
    {
        $this->inner->next();
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return $this->inner->valid();
    }

    public function rewind()
    {
        $this->inner->rewind();
        $this->position = 0;
    }

    public function count()
    {
        return array_reduce($this->adapters, function ($count, RetrieverInterface $adapter) {
            return $count + $adapter->count();
        }, 0);
    }
}
