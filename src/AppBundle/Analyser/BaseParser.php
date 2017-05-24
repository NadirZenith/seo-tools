<?php
/**
 * Created by PhpStorm.
 * User: tino
 * Date: 5/23/17
 * Time: 7:20 PM
 */

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use AppBundle\Services\HttpClient;
use AppBundle\Services\LinkProcessorOptions;
use Doctrine\ORM\EntityManager;

class BaseParser
{
    private $client;

    private $entityManager;

    /**
     * ChildLinksAnalyser constructor.
     * @param HttpClient $client
     * @param EntityManager $entityManager
     */
    public function __construct(HttpClient $client, EntityManager $entityManager)
    {
        $this->client = $client;

        $this->entityManager = $entityManager;
    }

    /**
     * @param Link $link
     * @param $url
     * @param LinkProcessorOptions $options
     * @return bool
     */
    protected function createLinkChildren(Link $link, $url, LinkProcessorOptions $options)
    {

        $childLink = $link->createChild($url);

        if ($this->isLinkValid($childLink, $options) && !$this->isLinkInHierarchy($link, $childLink)) {
            return $link->addChildren($childLink);
        }
    }


    /**
     * @param $childLink
     * @param LinkProcessorOptions $options
     * @return bool
     */
    private function isLinkValid(Link $childLink, LinkProcessorOptions $options)
    {

        // mailto urls
        if (in_array($childLink->getScheme(), ['mailto'])) {
            return false;
        }

        // skip ignored paths urls
        if ($this->matchPatterns($childLink->getPath(), $options->getIgnoredPathPatterns())) {
            return false;
        }

        // skip ignored urls
        if ($this->matchPatterns($childLink->getUrl(), $options->getIgnoredUrlPatterns())) {
            return false;
        }

        if (!filter_var($childLink->getUrl(), FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED)) {
            return false;
        };

        return true;
    }

    /**
     * @param Link $link
     * @param Link $childLink
     * @return bool
     */
    private function isLinkInHierarchy(Link $link, Link $childLink)
    {
        if (!$link->getId()) {
            return false;
        }

        $result = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Link::class, 'l')
            ->where('l.url = :url')
            ->andWhere('l.root = :root')
            ->setParameters(
                [
                    'url'  => $childLink->getUrl(),
                    'root' => $link->getRoot()
                ]
            )
            ->getQuery()
            ->getResult();

        return empty($result) ? false : true;
    }

    /**
     * @param string $url
     * @param array $patterns
     * @return bool
     */
    private function matchPatterns($url, array $patterns)
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        return false;
    }
}
