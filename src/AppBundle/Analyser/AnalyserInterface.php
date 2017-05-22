<?php

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use AppBundle\Services\LinkProcessorOptions;
use GuzzleHttp\Psr7\Response;

interface AnalyserInterface
{

    /**
     * @param Link $link
     * @param Response $response
     * @param LinkProcessorOptions $options
     * @return void
     */
    public function analyse(Link $link, Response $response, LinkProcessorOptions $options);
}
