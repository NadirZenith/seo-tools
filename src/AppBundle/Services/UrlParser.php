<?php

namespace AppBundle\Services;

use AppBundle\Entity\Link;
use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DomCrawler\Crawler;

class UrlParser
{

    private $browser;

    private $entityManager;

    public function __construct(Browser $buzz, EntityManager $entityManager)
    {
        $this->browser = $buzz;
        $this->browser->setClient(new Curl());
        $this->browser->getClient()->setTimeout(5000);
        $this->browser->getClient()->setVerifyPeer(false);
        $this->browser->getClient()->setIgnoreErrors(true);

        $this->entityManager = $entityManager;
    }

    public function parse(Link $link, $options = array())
    {

        $options = $this->initOptions($options);

        try {
            /**
             * @var Response $response
             */
            $response = $this->browser->get($link->getUrl());

            // if is root link, query for sitemap.xml
            if ($link->isRoot()) {
                $sitemapLink = new Link(sprintf("%s://%s/sitemap.xml", $link->getScheme(), $link->getHost()), Link::TYPE_SITEMAP);

                /**
                 * @var Response $robotsRsp
                 */
                $robotsRsp = $this->browser->get(sprintf("%s://%s/robots.txt", $link->getScheme(), $link->getHost()));

                if ($robotsRsp->getStatusCode() === 200) {
                    // robots.txt exist
                    $link->setRobots($robotsRsp->getContent());

                    preg_match_all('/Sitemap2: ([^\s]+)/', $link->getRobots(), $match);
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
                d('site map url ' . $sitemapLink->getUrl());
                $sitemapRsp = $this->browser->get($sitemapLink->getUrl());
                $sitemapLink->setResponse($sitemapRsp->getContent());

                $crawler = new Crawler($sitemapLink->getResponse());
                $crawler = $crawler->filterXPath('//default:sitemapindex/sitemap/loc');
                dd($crawler->html());
                $crawler = $crawler->filter('default|sitemapindex sitemap|group yt|aspectRatio');

                foreach ($crawler as $domElement) {
                    d($domElement->nodeName);
                    d($domElement->value);
                }

                dd($crawler->getNode(0)->getElementsByTagName('loc')->item(0)->textContent);

//                d($crawler->html());
//                dd($crawler->filter('sitemap')->count());
                $pattern = '(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
                preg_match_all("#$pattern#i", $sitemapLink->getResponse(), $match);
                if (isset($match[1]) && !empty($match[1])) {
                    // sitemap contains urls
//                    $matched_urls = $match[1];


//                    dd($urls);
                }
                dd('sitemap no urls');
            };
        } catch (\Exception $e) {
            $link->setStatus(Link::STATUS_SKIPPED);
            $link->setStatusMessage(sprintf("Browser Exception: %s", $e->getMessage()));
            dd('Debug exception: ' . $e->getMessage());
            return;
        }

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
                    'url' => $url,
                    'title' => $node->getAttribute('title'),
                    'text' => $node->textContent
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

    private function matchPatterns($url, $patterns)
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        return false;
    }

    private function isLinkInHierarchy(Link $link, $childLink)
    {

        $result = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Link::class, 'l')
            ->where('l.url = :url')
            ->andWhere('l.root = :root')
            ->setParameters(
                [
                    'url' => $childLink->getUrl(),
                    'root' => $link->getRoot()
                ]
            )
            ->getQuery()
            ->getResult();

        return empty($result) ? false : true;
    }
}
