<?php

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DomCrawler\Crawler;

class DefaultHtmlParser extends BaseParser implements AnalyserInterface
{
    const NAME = 'html';

    /**
     * @inheritdoc
     */
    public function analyse(Link $link, Response $response, array $options)
    {

        if (strpos($link->getResponseHeader('Content-Type'), 'text/html') === false) {
            return false;
        }

        $this->analyseHtml($link, $options);

        return true;
    }

    /**
     * @param Link $link
     * @param $options
     */
    private function analyseHtml(Link $link, $options)
    {
        $crawler = new Crawler($link->getResponse());

        // title
        if ($value = $this->getNodeValue($crawler->filter('head > title'))) {
            $link->setMeta('title', $value);
        }

        // description
        if ($value = $this->getNodeValue($crawler->filterXPath('//meta[@name="description"]/@content'))) {
            $link->setMeta('description', $value);
        }

        $this->analyseHeaders($link, $crawler, 'h1');
        $this->analyseHeaders($link, $crawler, 'h2');
        $this->analyseHeaders($link, $crawler, 'h3');

        // link nodes
        $nodes = $crawler->filter('a');
        $rawUrls = [];
        if ($nodes->count()) {
            /** @var \DOMElement $node */
            foreach ($nodes as $node) {
                $url = $node->getAttribute('href');

                $rawUrls[] = [
                    'url'   => $url,
                    'title' => $node->getAttribute('title'),
                    'text'  => $node->textContent,
                    'line'  => $node->getLineNo(),
                    'path'  => $node->getNodePath(),
                ];

                $this->createLinkChildren($link, $url, $options);
            }
        }
        $link->setRawUrls($rawUrls);

        // img nodes
        $nodes = $crawler->filter('img');
        $rawImgs = [];
        if ($nodes->count()) {
            /** @var \DOMElement $node */
            foreach ($nodes as $node) {
                $url = $node->getAttribute('src');
                $rawImgs[] = [
                    'url'      => $url,
                    'alt'      => $node->getAttribute('alt'),
                    'line'     => $node->getLineNo(),
                    'path'     => $node->getNodePath(),
                    // project specific @todo maybe get from options or get all attributes
                    'data-src' => $node->getAttribute('data-src')
                ];
            }
        }

        $link->setRawImgs($rawImgs);
    }


    /**
     * @param Crawler $node
     * @param string $key
     * @return bool|string
     */
    private function getNodeValue(Crawler $node, $key = 'text')
    {

        if ($node->count()) {
            return $node->$key();
        }

        return false;
    }

    /**
     * @param Crawler $nodes
     * @return array
     */
    private function getNodeValues(Crawler $nodes)
    {
        $values = [];
        if ($nodes->count()) {
            foreach ($nodes as $node) {
                array_push($values, $node->textContent);
            }
        }

        return $values;
    }

    /**
     * @param Link $link
     * @param $crawler
     * @param string $header
     * @internal param $string
     */
    private function analyseHeaders(Link $link, Crawler $crawler, $header = 'h1')
    {

        if ($values = $this->getNodeValues($crawler->filterXPath("//$header"))) {
            foreach ($values as $k => $value) {
                $link->setMeta(sprintf("$header::%d", ++$k), $value);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::NAME;
    }
}
