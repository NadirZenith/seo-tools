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


        // if is root link, query for sitemap.xml
//        if ($link->isRoot()) {
        if ($link->isRoot() || $link->getSource() === Link::SOURCE_ROBOTS) {

            // @todo if this moves to robots, the children link will contain source robots(its true :))
            $sitemapLink = $this->createLinkChildren($link, "sitemap.xml", $options);
//            $sitemapLink->setSource(Link::SOURCE_SITEMAP);

            // try to get sitemap url from robots if exist
//            $this->analyseRobots($link, $sitemapLink);

            return true; // no more parsers
        };

        // if is sitemap source link, find links
        if (strpos($link->getResponseHeader('Content-Type'), '/xml') === false) {
            return false; // continue parsing
        }

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

        return true; // no more parsers
    }

    /**
     * @param Link $link
     * @param Link $sitemapLink
     * @todo move to robotsParser
     */
    private function analyseRobots(Link $link, Link $sitemapLink)
    {

        preg_match_all('/Sitemap: ([^\s]+)/', $link->getResponse(), $match);

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


    public function getName()
    {
        return self::NAME;
    }
}
