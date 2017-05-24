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

            $robotsLink = $link->createChild("robots.txt");
            $robotsLink->setSource(Link::SOURCE_ROBOTS);

            $link->addChildren($robotsLink);

            return true;// no more parsers
        };

        return false; // continue parsing

    }

    public function getName()
    {
        return self::NAME;
    }

}
