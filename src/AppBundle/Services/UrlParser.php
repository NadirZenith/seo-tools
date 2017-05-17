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

            if($link->isRoot()) {
                $robotsRsp = $this->browser->get(sprintf("%s://%s/robots.txt", $link->getScheme(), $link->getHost()));
                if($robotsRsp->getStatusCode() === 200) {
                    $link->setRobots($robotsRsp->getContent());
                }
            };

        } catch (\Exception $e) {
            $link->setStatus(Link::STATUS_SKIPPED);
            $link->setStatusMessage(sprintf("Browser Exception: %s", $e->getMessage()));
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
        $title_node = $crawler->filter('head > title');
        if ($title_node->count()) {
            $link->setMeta('title', $title_node->text());
        }

        // description
        $meta_description_node = $crawler->filterXPath('//meta[@name="description"]');
        if ($meta_description_node->count()) {
            $link->setMeta('description', $meta_description_node->attr('content'));
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

        $links_node = $crawler->filter('a');
        $raw_urls = array();
        if ($links_node->count()) {
            foreach ($links_node as $link_node) {
                $url = $link_node->getAttribute('href');

                // ignore hash or empty url
                if (empty($url) || in_array(substr($url, 0, 1), ['#', '?'])) {
                    continue;
                }
                $raw_urls[] = [
                    'url' => $url,
                    'title' => $link_node->getAttribute('title'),
                    'text' => $link_node->textContent
                ];
            }
        }
        $link->setRawUrls($raw_urls);

        foreach ($raw_urls as $url) {
            $childLink = new Link($url['url']);

            //            dump($childLink->getUrl());
            // skip ignore patterns urls
            if ($this->matchPatterns($childLink->getPath(), $options->getIgnoredPathPatterns())) {
                continue;
            }

            if ($this->matchPatterns($childLink->getUrl(), $options->getIgnoredUrlPatterns())) {
                continue;
            }

            // mailto urls
            if (in_array($childLink->getScheme(), array('mailto'))) {
                continue;
            }

            // check if child url is relative and has a path (ex: is not a #hash url)
            if (!$childLink->getHost() && $childLink->getPath()) {
                // child link url is relative, prepend link scheme and host
                $childLink->setType(Link::TYPE_INTERNAL);

                $absolute_url = sprintf("%s://%s%s", $link->getScheme(), $link->getHost(), $childLink->getPath());
                $childLink->setUrl($absolute_url);
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