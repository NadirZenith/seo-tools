<?php

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use GuzzleHttp\Psr7\Response;

interface AnalyserInterface
{

    /**
     * @param Link $link
     * @param Response $response
     * @param array $options
     * @return bool
     */
    public function analyse(Link $link, Response $response, array $options);

    /**
     * @return string
     */
    public function getName();
}
