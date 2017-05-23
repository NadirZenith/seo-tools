<?php

namespace AppBundle\Services;

use AppBundle\Analyser\AnalyserInterface;
use AppBundle\Analyser\ChildLinksAnalyser;
use AppBundle\Analyser\RobotsAnalyser;
use AppBundle\Analyser\SitemapAnalyser;
use AppBundle\Analyser\StandardHtmlAnalyser;
use AppBundle\Entity\Link;
use Doctrine\ORM\EntityManager;

class LinkProcessor
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var AnalyserInterface[]
     */
    private $analysers = [];

    /**
     * UrlParser constructor.
     * @param HttpClient $client
     * @param EntityManager $entityManager
     */
    public function __construct(HttpClient $client, EntityManager $entityManager)
    {
        $this->client = $client;

        // analyse tags (metas, a, imgs, etc...)
        $this->addAnalyser(new StandardHtmlAnalyser());

        // customs extra
        $this->addAnalyser(new RobotsAnalyser($client));
        $this->addAnalyser(new SitemapAnalyser($client));

        // convert previous urls into links
        $this->addAnalyser(new ChildLinksAnalyser($entityManager));
    }

    /**
     * @param AnalyserInterface $analyser
     */
    public function addAnalyser(AnalyserInterface $analyser)
    {
        array_push($this->analysers, $analyser);
    }

    /**
     * @param Link $link
     * @param array|LinkProcessorOptions $options
     * @return bool
     */
    public function process(Link $link, $options = [])
    {

        $options = $this->initOptions($options);

        try {
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this->client->get($link->getUrl());

            $link->setCheckedAt(new \DateTime());
            $link->setRedirects($this->client->getRedirects());
            $link->setMeta('transferTime', $this->client->getTransferTime());

            $link->setResponseHeaders($response->getHeaders());
            $link->setStatusCode($response->getStatusCode());
            $link->setResponse($response->getBody()->getContents());

            foreach ($this->analysers as $analyser) {
                $analyser->analyse($link, $response, $options);

                // if it is an external link, don't need to analyse more
                if ($link->getType() === Link::TYPE_EXTERNAL) {
                    break;
                }
            }
        } catch (\Exception $e) {
            $link->setStatus(Link::STATUS_SKIPPED);
            $link->setStatusMessage(sprintf('Browser exception: %s in %s:%d ', $e->getMessage(), $e->getFile(), $e->getLine()));
//            dd(sprintf('Debug exception: %s in %s:%d ', $e->getMessage(), $e->getFile(), $e->getLine()));
            return false;
        }

        $link->setStatus(Link::STATUS_PARSED);

        return true;
    }

    /**
     * @param $options
     * @return LinkProcessorOptions
     */
    private function initOptions($options)
    {
        if ($options instanceof LinkProcessorOptions) {
            return $options;
        }

        if (is_array($options)) {
            return new LinkProcessorOptions($options);
        }

        throw new \InvalidArgumentException(sprintf("Url parser accepts an array or an UrlParserOptions, %s given.", gettype($options)));
    }
}
