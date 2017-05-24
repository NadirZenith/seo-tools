<?php

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Response;

class ChildLinksAnalyser implements AnalyserInterface
{

    private $entityManager;

    /**
     * ChildLinksAnalyser constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritdoc
     */
    public function analyse(Link $link, Response $response, array $options)
    {

        foreach ($link->getRawUrls() as $url) {
            $childLink = $link->createChild($url['url']);

            if (!$this->isLinkValid($childLink, $options)) {
                continue;
            }

            if (!$this->isLinkInHierarchy($link, $childLink)) {
                $link->addChildren($childLink);
            }
        }
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
     * @param $childLink
     * @param array $options
     * @return bool
     */
    private function isLinkValid($childLink, array $options)
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
}
