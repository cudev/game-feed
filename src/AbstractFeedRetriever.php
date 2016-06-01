<?php

namespace GameFeed;

use GameFeed\Exceptions\RetrieverException;
use SimpleXMLElement;

abstract class AbstractFeedRetriever extends AbstractRetriever
{
    protected $url;
    protected $channelName = 'channel';
    protected $itemName = 'item';

    public function count()
    {
        $simpleXmlElement = $this->queryAndDecode();
        return count($simpleXmlElement->{$this->channelName}->{$this->itemName});
    }

    protected function scrape()
    {
        $simpleXmlElement = $this->queryAndDecode();

        /** @var SimpleXMLElement $item */
        foreach ($simpleXmlElement->{$this->channelName}->{$this->itemName} as $item) {
            yield $item->asXML();
        }
    }

    private function queryAndDecode()
    {
        $contents = $this->query($this->url);
        $simpleXmlElement = simplexml_load_string($contents, null, LIBXML_NOCDATA);

        if ($simpleXmlElement === false) {
            $error = libxml_get_last_error();
            throw new RetrieverException($error->message, $error->code);
        }

        /** @noinspection IsEmptyFunctionUsageInspection */
        if (empty($simpleXmlElement->{$this->channelName}->{$this->itemName})) {
            throw new RetrieverException(
                sprintf('Cannot find %s and %s nodes in XML feed', $this->channelName, $this->itemName)
            );
        }

        return $simpleXmlElement;
    }
}
