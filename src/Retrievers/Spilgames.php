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

namespace GameFeed\Retrievers;

use GameFeed\AbstractRetriever;
use GameFeed\Exceptions\RetrieverException;
use GuzzleHttp\Exception\RequestException;

class Spilgames extends AbstractRetriever
{
    private $url = 'http://publishers.spilgames.com/en/rss-3';
    private $query = [
        'limit' => 100,
        'format' => 'json',
        'page' => 1
    ];


    public function count()
    {
        $contentsDecoded = $this->queryAndDecode();
        return $contentsDecoded['totalEntries'];
    }

    protected function scrape()
    {
        do {
            $contentsDecoded = $this->queryAndDecode();

            foreach ($contentsDecoded['entries'] as $entry) {
                yield $entry['id'] => $entry;
            }

            $this->query['page']++;
        } while ($this->query['page'] <= $contentsDecoded['totalPages']);
    }

    private function queryAndDecode()
    {
        try {
            $contents = $this->query($this->url . '?' . http_build_query($this->query));
        } catch (RequestException $exception) {
            throw new RetrieverException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $contentsDecoded = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RetrieverException(json_last_error_msg(), json_last_error());
        }

        $requiredKeys = array_flip(['entries', 'totalPages', 'totalEntries']);
        if (count(array_intersect_key($requiredKeys, $contentsDecoded)) !== count($requiredKeys)) {
            throw new RetrieverException('Unknown response format from Spilgames');
        }

        return $contentsDecoded;
    }
}
