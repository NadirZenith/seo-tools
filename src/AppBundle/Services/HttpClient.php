<?php

namespace AppBundle\Services;

use GuzzleHttp\Client;
use GuzzleHttp\RedirectMiddleware;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class HttpClient extends Client
{
    const HISTORY_HEADER = RedirectMiddleware::HISTORY_HEADER;
    private $redirects;

    public function __call($method, $args)
    {
        $this->redirects = [];
        $onRedirect = function (RequestInterface $request, ResponseInterface $response, UriInterface $uri) {
            $this->redirects[][$response->getStatusCode()] = (string)$uri;
        };

        $args[1][RequestOptions::ALLOW_REDIRECTS] = ['on_redirect' => $onRedirect];

        $response = parent::__call($method, $args);

        return $response;
//        return $response->withAddedHeader(self::HISTORY_HEADER, json_encode($this->getRedirects()));
    }

    public function getRedirects()
    {
        return $this->redirects;
    }
}
