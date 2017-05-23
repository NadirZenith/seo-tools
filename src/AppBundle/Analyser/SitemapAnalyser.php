<?php

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use AppBundle\Services\HttpClient;
use AppBundle\Services\LinkProcessorOptions;
use GuzzleHttp\Psr7\Response;
use SimpleXMLElement;

class SitemapAnalyser implements AnalyserInterface
{
    private $client;

    /**
     * SitemapAnalyser constructor.
     * @param HttpClient $client
     */
    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param Link $link
     * @param Response $response
     * @param LinkProcessorOptions $options
     * @return void
     */
    public function analyse(Link $link, Response $response, LinkProcessorOptions $options)
    {

        // if is root link, query for sitemap.xml
        if ($link->isRoot()) {
//            $sitemapLink = new Link(sprintf("%s://%s/sitemap.xml", $link->getScheme(), $link->getHost()), Link::SOURCE_SITEMAP);
            $sitemapLink = $link->createChild(sprintf("%s://%s/sitemap.xml", $link->getScheme(), $link->getHost()));
            $sitemapLink->setSource(Link::SOURCE_SITEMAP);

            // try to get sitemap url from robots if exist
            $this->analyseRobots($link, $sitemapLink);

            $link->addChildren($sitemapLink);

            return;
        };

        // if is sitemap source link, find links
        if (Link::SOURCE_SITEMAP === $link->getSource()) {
            $key = 'url';

            $sitemapXml = new SimpleXmlElement($link->getResponse());
            if (isset($sitemapXml->sitemap)) {
                // sitemap is a index sitemap which contains urls to multiple sitemaps
                $key = 'sitemap';
            }

            $rawUrls = [];
            foreach ($sitemapXml->$key as $url) {
                $rawUrls[] = [
                    'url' => strval($url->loc)
                ];
            }
            $link->setRawUrls($rawUrls);
        }
    }

    /**
     * @param Link $link
     * @param Link $sitemapLink
     */
    private function analyseRobots(Link $link, Link $sitemapLink)
    {
        if (!$link->getRobots()) {
            return;
        }

        preg_match_all('/Sitemap: ([^\s]+)/', $link->getRobots(), $match);
        if (isset($match[1], $match[1][0]) && !empty($match[1][0])) {
            // Sitemap url found on robots.txt, use it to get sitemap url
            $sitemapLink->setUrl($match[1][0]);

            // check if child url is relative and has a path (ex: is not a #hash url)
            if (!$sitemapLink->getHost() && $sitemapLink->getPath()) {
                // child link url is relative, prepend url scheme and host

                $absoluteUrl = sprintf("%s://%s%s", $link->getScheme(), $link->getHost(), $sitemapLink->getPath());
                $sitemapLink->setUrl($absoluteUrl);
            }
        }
    }
}
