<?php

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use GuzzleHttp\Psr7\Response;

class RobotsAnalyser extends BaseParser implements AnalyserInterface
{
    const NAME = 'robots';

    /**
     * @inheritdoc
     */
    public function analyse(Link $link, Response $response, array $options)
    {
        // if is root link, query for robots
        if ($link->isRoot()) {
            $this->createLinkChildren($link, "robots.txt", $options);

            return true;
        };

        if (strpos($link->getResponseHeader('Content-Type'), 'text/plain') === false) {
            return false;
        }

        $this->analyseRobots($link, $options);

        return true;
    }

    /**
     * @param Link $link
     * @param $options
     * @internal param Link $sitemapLink
     * @todo match remaining sitemap links
     */
    private function analyseRobots(Link $link, $options)
    {

        preg_match_all('/Sitemap: ([^\s]+)/', $link->getResponse(), $match);

        if (isset($match[1], $match[1][0]) && !empty($match[1][0])) {
            // Sitemap url found on robots.txt, use it to get sitemap url
            $this->createLinkChildren($link, $match[1][0], $options);
        }
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::NAME;
    }
}
