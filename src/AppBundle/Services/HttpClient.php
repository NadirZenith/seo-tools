<?php

namespace AppBundle\Services;

use GuzzleHttp\Client;
use GuzzleHttp\RedirectMiddleware;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class HttpClient extends Client
{
    const HISTORY_HEADER = RedirectMiddleware::HISTORY_HEADER;
    private $redirects;
    private $transferTime;

    /**
     * @param $method
     * @param $args
     * @return \GuzzleHttp\Promise\PromiseInterface|mixed|ResponseInterface
     */
    public function __call($method, $args)
    {
        $args = $this->setUpRedirectWatcher($args);
        $args = $this->setUpTransferTimeWatcher($args);

        $response = parent::__call($method, $args);

        return $response;
    }


    /**
     * @return array
     */
    public function getRedirects()
    {
        return $this->redirects;
    }

    /**
     * @return float|null
     */
    public function getTransferTime()
    {
        return $this->transferTime;
    }

    /**
     * @param $args
     * @return array
     */
    private function setUpRedirectWatcher($args)
    {
        $this->redirects = [];
        $onRedirect = function (RequestInterface $request, ResponseInterface $response, UriInterface $uri) {
            $this->redirects[][$response->getStatusCode()] = (string)$uri;
        };
        $args[1][RequestOptions::ALLOW_REDIRECTS] = ['on_redirect' => $onRedirect];

        return $args;
    }

    /**
     * @param $args
     * @return array
     */
    private function setUpTransferTimeWatcher($args)
    {
        $this->transferTime = null;
        $onStats = function (TransferStats $stats) {
            $this->transferTime = $stats->getTransferTime();
        };
        $args[1][RequestOptions::ON_STATS] = $onStats;

        return $args;
    }
}
