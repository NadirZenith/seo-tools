<?php
/**
 * This file is part of the seo-tools package.
 */

namespace AppBundle\Services;

use AppBundle\Analyser\AnalyserInterface;
use AppBundle\Analyser\ChildLinksAnalyser;
use AppBundle\Analyser\RobotsAnalyser;
use AppBundle\Analyser\DefaultSitemapParser;
use AppBundle\Analyser\DefaultHtmlParser;
use AppBundle\Entity\Link;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class LinkProcessor
 * @package AppBundle\Services
 */
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

        // customs extra
        $this->addAnalyser(new RobotsAnalyser($entityManager));

        $this->addAnalyser(new DefaultSitemapParser($entityManager));

        // analyse tags (metas, a, imgs, etc...)
        $this->addAnalyser(new DefaultHtmlParser($entityManager));
    }

    /**
     * @param AnalyserInterface $analyser
     */
    public function addAnalyser(AnalyserInterface $analyser)
    {
        $this->analysers[$analyser->getName()] = $analyser;
    }

    /**
     * @param Link $link
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function process(Link $link, $options = [])
    {
        $options = $this->initOptions($options);

        try {
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this->client->get($link->getUrl());

            $this->processResponse($link, $response, $options);
        } catch (\Exception $e) {
            $link->setStatus(Link::STATUS_SKIPPED);
            $link->setStatusMessage(sprintf('Browser exception: %s (in %s:%d)', $e->getMessage(), $e->getFile(), $e->getLine()));
            if (!$options['force']) {
//                dd(sprintf('Browser exception: %s (in %s:%d)', $e->getMessage(), $e->getFile(), $e->getLine()));
                throw new \Exception(sprintf('Browser exception: %s (in %s:%d)', $e->getMessage(), $e->getFile(), $e->getLine()));
            }
            return false;
        }

        $link->setStatus(Link::STATUS_PARSED);

        return true;
    }

    /**
     * @param $options
     * @return array
     */
    private function initOptions($options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'force'                 => false,
            'parsers'               => array_keys($this->analysers),
//            'parsers'               => [RobotsAnalyser::NAME, DefaultSitemapParser::NAME, DefaultHtmlParser::NAME],
            'ignored_url_patterns'  => [],
            'ignored_path_patterns' => [],
            'image_patterns'        => ['/.(?:jpe?g|gif|png|mp4)/i']// @todo file patterns too??
        ]);

        $resolver->setAllowedTypes('parsers', ['array']);
        $resolver->setAllowedTypes('ignored_url_patterns', ['array']);
        $resolver->setAllowedTypes('ignored_path_patterns', ['array']);
        $resolver->setAllowedTypes('image_patterns', ['array']);
        $resolver->setAllowedTypes('force', ['bool']);
        $options = $resolver->resolve($options);

        return $options;
    }

    /**
     * @param Link $link
     * @param Response $response
     * @param array $options
     * @throws \Exception
     */
    private function processResponse(Link $link, Response $response, $options)
    {
        $link->setCheckedAt(new \DateTime());
        $link->setRedirects($this->client->getRedirects());
        $link->setMeta('transferTime', $this->client->getTransferTime());

        $link->setResponseHeaders($response->getHeaders());
        $link->setStatusCode($response->getStatusCode());
        $link->setResponse($response->getBody()->getContents());

        // if it is an external link, don't need to analyse more
        if ($link->getType() !== Link::TYPE_INTERNAL) {
            return;
        }

        foreach ($options['parsers'] as $parser) {
            if (!isset($this->analysers[$parser])) {
                throw new \Exception(sprintf("Parser %s not found. Available parsers are %", $parser));
            }

            $this->analysers[$parser]->analyse($link, $response, $options);
        }
    }
}
