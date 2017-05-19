<?php

namespace AppBundle\Services;

use AppBundle\Entity\Link;
use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Doctrine\ORM\EntityManager;
use SimpleXMLElement;
use Symfony\Component\DomCrawler\Crawler;

class UrlParser
{

    private $browser;

    private $entityManager;

    /**
     * UrlParser constructor.
     * @param Browser $buzz
     * @param EntityManager $entityManager
     */
    public function __construct(Browser $buzz, EntityManager $entityManager)
    {
        $this->browser = $buzz;
        $this->browser->setClient(new Curl());
        $this->browser->getClient()->setTimeout(5000);
        $this->browser->getClient()->setVerifyPeer(false);
        $this->browser->getClient()->setIgnoreErrors(true);

        $this->entityManager = $entityManager;
    }

    /**
     * @param Link $link
     * @param array|UrlParserOptions $options
     */
    public function parse(Link $link, $options = array())
    {

        $options = $this->initOptions($options);

        try {
            /**
             * @var Response $response
             */
//            $response = $this->browser->get($link->getUrl());

            // if is root link, query for sitemap.xml
            if ($link->isRoot() && false) {
                $sitemapLink = new Link(sprintf("%s://%s/sitemap.xml", $link->getScheme(), $link->getHost()), Link::TYPE_SITEMAP);

                /**
                 * @var Response $robotsRsp
                 */
                $robotsRsp = $this->browser->get(sprintf("%s://%s/robots.txt", $link->getScheme(), $link->getHost()));

                if ($robotsRsp->getStatusCode() === 200) {
                    d('robots exist');

                    // robots.txt exist
                    $link->setRobots($robotsRsp->getContent());

                    preg_match_all('/Sitemap: ([^\s]+)/', $link->getRobots(), $match);
                    if (isset($match[1], $match[1][0]) && !empty($match[1][0])) {
                        d('robots contains sitemap url');
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
                d('site map url ' . $sitemapLink->getUrl());
                $sitemapRsp = $this->browser->get('http://www.schweppes-sf.dev/sitemap.xml');
//                $sitemapRsp = $this->browser->get($sitemapLink->getUrl());
                $sitemapLink->setResponse($sitemapRsp->getContent());
                $sitemapXml = new SimpleXmlElement($sitemapLink->getResponse());
                $urls = [];
                $key = 'url';
                if (isset($sitemapXml->sitemap)) {
                    // sitemap is a index sitemap which contains urls to multiple sitemaps
                    $key = 'sitemap';
                }

                foreach ($sitemapXml->$key as $url) {
                    $urls[] = strval($url->loc);
                }
            };
        } catch (\Exception $e) {
            $link->setStatus(Link::STATUS_SKIPPED);
            $link->setStatusMessage(sprintf("Browser Exception: %s", $e->getMessage()));
            dd('Debug exception: ' . $e->getMessage());
            return;
        }
        dd('fill link with response');
        $link->setCheckedAt(new \DateTime());
        $link->setStatusCode($response->getStatusCode());

        // if it is an external link, don't need to crawl more urls
        if ($link->getType() === Link::TYPE_EXTERNAL) {
            $link->setStatus(Link::STATUS_PARSED);
            return;
        }

        $link->setResponse($response->getContent());
        $link->setResponseHeaders($response->getHeaders());
        $crawler = new Crawler($link->getResponse());

        // title
        $node = $crawler->filter('head > title');
        if ($node->count()) {
            $link->setMeta('title', $node->text());
        }

        // description
        $node = $crawler->filterXPath('//meta[@name="description"]');
        if ($node->count()) {
            $link->setMeta('description', $node->attr('content'));
        }

        // h1
        $nodes = $crawler->filterXPath('//h1');
        if ($nodes->count()) {
            foreach ($nodes as $k => $node) {
                $link->setMeta(sprintf('h1::%d', $k), $node->textContent);
            }
        }
        // h2
        $nodes = $crawler->filterXPath('//h2');
        if ($nodes->count()) {
            foreach ($nodes as $k => $node) {
                $link->setMeta(sprintf('h2::%d', $k), $node->textContent);
            }
        }
        // h3
        $nodes = $crawler->filterXPath('//h3');
        if ($nodes->count()) {
            foreach ($nodes as $k => $node) {
                $link->setMeta(sprintf('h3::%d', $k), $node->textContent);
            }
        }

        // link nodes
        $nodes = $crawler->filter('a');
        $rawUrls = array();
        if ($nodes->count()) {
            foreach ($nodes as $node) {
                $url = $node->getAttribute('href');

                // ignore hash or empty url
                if (empty($url) || in_array(substr($url, 0, 1), ['#', '?'])) {
                    continue;
                }
                $rawUrls[] = [
                    'url'   => $url,
                    'title' => $node->getAttribute('title'),
                    'text'  => $node->textContent
                ];
            }
        }
        $link->setRawUrls($rawUrls);

        foreach ($rawUrls as $url) {
            $childLink = new Link($url['url']);

            // mailto urls
            if (in_array($childLink->getScheme(), array('mailto'))) {
                continue;
            }
            // skip ignore patterns urls
            if ($this->matchPatterns($childLink->getPath(), $options->getIgnoredPathPatterns())) {
                continue;
            }

            if ($this->matchPatterns($childLink->getUrl(), $options->getIgnoredUrlPatterns())) {
                continue;
            }

            // check if child url is relative and has a path (ex: is not a #hash url)
            if (!$childLink->getHost() && $childLink->getPath()) {
                // child link url is relative, prepend link scheme and host
                $childLink->setType(Link::TYPE_INTERNAL);

                $absoluteUrl = sprintf("%s://%s%s", $link->getScheme(), $link->getHost(), $childLink->getPath());
                $childLink->setUrl($absoluteUrl);
            }

            if ($link->getHost() !== $childLink->getHost()) {
                $childLink->setType(Link::TYPE_EXTERNAL);
            }

            if (!$this->isLinkInHierarchy($link, $childLink)) {
                $link->addChildren($childLink);
            }
        }

        $link->setStatus(Link::STATUS_PARSED);
    }

    /**
     * @param $options
     * @return UrlParserOptions
     */
    private function initOptions($options)
    {
        if ($options instanceof UrlParserOptions) {
            return $options;
        }

        if (is_array($options)) {
            return new UrlParserOptions($options);
        }

        throw new \InvalidArgumentException(sprintf("Url parser accepts an array or an UrlParserOptions, %s given.", gettype($options)));
    }

    /**
     * @param string $url
     * @param array $patterns
     * @return bool
     */
    private function matchPatterns($url, array $patterns)
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Link $link
     * @param Link $childLink
     * @return bool
     */
    private function isLinkInHierarchy(Link $link, Link $childLink)
    {

        $result = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Link::class, 'l')
            ->where('l.url = :url')
            ->andWhere('l.root = :root')
            ->setParameters(
                [
                    'url'  => $childLink->getUrl(),
                    'root' => $link->getRoot()
                ]
            )
            ->getQuery()
            ->getResult();

        return empty($result) ? false : true;
    }
}
