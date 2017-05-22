<?php

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use AppBundle\Services\LinkProcessorOptions;
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
     * @param Link $link
     * @param Response $response
     * @param LinkProcessorOptions $options
     */
    public function analyse(Link $link, Response $response, LinkProcessorOptions $options)
    {

        foreach ($link->getRawUrls() as $url) {
            $childLink = new Link($url['url'], $link->getSource());

            if (!$this->isLinkValid($childLink, $options)) {
                continue;
            }

            // check if child url is relative and has a path (ex: is not a #hash url)
            if (!$childLink->getHost() && $childLink->getPath()) {
                // child link url is relative, prepend link scheme and host

                $childLink->setUrl(sprintf("%s://%s%s", $link->getScheme(), $link->getHost(), $childLink->getPath()))
                    ->setType(Link::TYPE_INTERNAL);
            }

            if ($link->getHost() !== $childLink->getHost()) {
                $childLink->setType(Link::TYPE_EXTERNAL);
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
     * @param LinkProcessorOptions $options
     * @return bool
     */
    private function isLinkValid($childLink, LinkProcessorOptions $options)
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

        return true;
    }
}
