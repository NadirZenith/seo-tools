<?php

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use GuzzleHttp\Psr7\Response;
use SimpleXMLElement;

class DefaultSitemapParser extends BaseParser implements AnalyserInterface
{
    const NAME = 'sitemap';

    /**
     * @inheritdoc
     */
    public function analyse(Link $link, Response $response, array $options)
    {

        if ($link->isRoot()) {
            // if is root link, query for sitemap.xml
            $this->createLinkChildren($link, "sitemap.xml", $options);

            return true;
        };

        // if is sitemap source link, find links
        if (strpos($link->getResponseHeader('Content-Type'), '/xml') === false) {
            return false; // continue parsing
        }

        $this->analyseSitemap($link, $options);

        return true;
    }

    /**
     * @param Link $link
     * @param $options
     */
    private function analyseSitemap(Link $link, $options)
    {

        $sitemapXml = new SimpleXmlElement($link->getResponse());

        $key = 'url';
        if (isset($sitemapXml->sitemap)) {
            // sitemap is a index sitemap which contains urls to multiple sitemaps
            $key = 'sitemap';
        }

        $rawUrls = [];
        foreach ($sitemapXml->$key as $url) {
            $url = strval($url->loc);
            $rawUrls[] = [
                'url'        => $url,
                'lastmod'    => @strval($url->lastmod),
                'changefreq' => @strval($url->changefreq),
                'priority'   => @strval($url->priority),
            ];

            $this->createLinkChildren($link, $url, $options);
        }
        $link->setRawUrls($rawUrls);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::NAME;
    }
}
