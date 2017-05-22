<?php

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use AppBundle\Services\HttpClient;
use AppBundle\Services\LinkProcessorOptions;
use GuzzleHttp\Psr7\Response;

class RobotsAnalyser implements AnalyserInterface
{
    private $client;

    /**
     * RobotsAnalyser constructor.
     * @param HttpClient $client
     */
    public function __construct($client)
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

        // if is root link, query for robots
        if (!$link->isRoot()) {
            return;
        };

        /** @var \GuzzleHttp\Psr7\Response $response */
        $response = $this->client->get(sprintf("%s://%s/robots.txt", $link->getScheme(), $link->getHost()));

        if ($response->getStatusCode() !== 200) {
            // @todo set note in link about not fount robots
            return;
        }

        // robots.txt exist
        $link->setRobots($response->getBody()->getContents());
    }
}
