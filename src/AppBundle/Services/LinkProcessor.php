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
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
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
     * @var string[]
     */
    private $userAgents = [];

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

        $this->userAgents = explode("\n", file_get_contents(__DIR__ . '/UserAgents'));
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

        $request = $this->createRequest($link);

        $requestOptions = ['timeout' => 10];

        try {
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this->client->send($request, $requestOptions);

            $this->processResponse($link, $response, $options);

            $link->setStatus(Link::STATUS_PARSED);

            return true;
        } catch (\Exception $e) {
            $link->setStatus(Link::STATUS_SKIPPED);
            $link->setStatusMessage(sprintf('Browser exception: %s (in %s:%d)', $e->getMessage(), $e->getFile(), $e->getLine()));
            if ($options['force']) {
                return false;
            }

            throw new \Exception(sprintf('Browser exception: %s (in %s:%d)', $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * @param Link[] $links
     * @param array $options
     * @param \Closure $each
     * @param \Closure $final
     * @return bool
     */
    public function processAsync(array $links, $options = [], \Closure $each = null, \Closure $final = null)
    {
        $options = $this->initOptions($options);

        // requests from links
        $requests = $this->getLinksRequests();

        $requestOptions = ['timeout' => 10];

        // pool
        $poolOptions = [
            'options' => $requestOptions,
            'concurrency' => 5,
            'fulfilled' => function (Response $response, $i) use ($links, $options, $each) {
                // this is delivered each successful response
//                $link = $links[$i];
                $this->processResponse($links[$i], $response, $options);
                $links[$i]->setStatus(Link::STATUS_PARSED);

                if ($each) {
                    $each($links[$i], $i);
                }
            },
            'rejected' => function ($reason, $i) use ($links, $options, $each) {
                // this is delivered each failed request
                $links[$i]->setStatus(Link::STATUS_SKIPPED);
                $links[$i]->setStatusMessage(sprintf('Browser exception: %s (in %s:%d)', $reason->getMessage(), $reason->getFile(), $reason->getLine()));
//                if (!$options['force']) {
//                    throw new \Exception(sprintf('Browser exception: %s (in %s:%d)', $e->getMessage(), $e->getFile(), $e->getLine()));
//                }

                if ($each) {
                    $each($links[$i], $i, $reason);
                }
            },
        ];
        $pool = new Pool($this->client, $requests($links), $poolOptions);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        if ($final) {
            $promise->then($final, $final);
        }

        return true;
    }

    /**
     * @return \Closure
     */
    private function getLinksRequests()
    {
        return function ($links) {
            for ($i = 0; $i < count($links); $i++) {
                yield $this->createRequest($links[$i]);
            }
        };
    }

    /**
     * @param $options
     * @return array
     */
    private function initOptions($options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'force' => false,
            'parsers' => array_keys($this->analysers),
            'ignored_url_patterns' => [],
            'ignored_path_patterns' => [],
            'image_patterns' => ['/.(?:jpe?g|gif|png|mp4|pdf)/i']// @todo file patterns too??
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

    private function createRequest(Link $link)
    {
        $method = $link->getType() === Link::TYPE_EXTERNAL ? 'HEAD' : 'GET';

        return new Request($method, $link->getUrl(), [
            'User-Agent' => $this->userAgents[array_rand($this->userAgents)]
        ]);
    }
}
