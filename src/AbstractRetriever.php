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

use DateInterval;
use Generator;
use GuzzleHttp\Client;
use Psr\Cache\CacheItemPoolInterface;

abstract class AbstractRetriever implements RetrieverInterface
{
    /** @var callable */
    protected $transformer;

    /** @var CacheItemPoolInterface */
    protected $cacheItemPool;

    /** @var DateInterval */
    protected $cacheExpiresAfter;

    /** @var Client */
    protected $httpClient;

    public function __construct(callable $transformer = null, CacheItemPoolInterface $cacheItemPool = null)
    {
        $this->httpClient = new Client();
        $this->transformer = $transformer;
        $this->cacheItemPool = $cacheItemPool;
        $this->cacheExpiresAfter = DateInterval::createFromDateString('1 day');
    }

    public function setTransformer(callable $transformer)
    {
        $this->transformer = $transformer;
        return $this;
    }

    public function setCacheItemPool(CacheItemPoolInterface $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
        return $this;
    }

    public function setCacheExpiresAfter(DateInterval $cacheExpiresAfter)
    {
        $this->cacheExpiresAfter = $cacheExpiresAfter;
        return $this;
    }

    public function setHttpClient(Client $httpClient)
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    public function retrieve(): Generator
    {
        $normaliser = $this->transformer;
        foreach ($this->scrape() as $game) {
            yield $normaliser !== null ? $normaliser($game) : $game;
        }
    }

    protected function query($url)
    {
        if ($this->cacheItemPool === null) {
            return $this->httpClient->get($url)->getBody()->getContents();
        }

        $cacheItem = $this->cacheItemPool->getItem('game-feed/' . md5($url));

        if (!$cacheItem->isHit()) {
            $cacheItem->set($this->httpClient->get($url)->getBody()->getContents())
                ->expiresAfter($this->cacheExpiresAfter);
            $this->cacheItemPool->save($cacheItem);
        }

        return $cacheItem->get();
    }

    abstract protected function scrape();
}
