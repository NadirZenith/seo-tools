<?php
/**
 * Created by PhpStorm.
 * User: tino
 * Date: 5/23/17
 * Time: 7:20 PM
 */

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use Doctrine\ORM\EntityManager;

abstract class BaseParser implements AnalyserInterface
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
     * @param $url
     * @param array $options
     * @return bool
     */
    protected function createLinkChildren(Link $link, $url, array $options)
    {
        if (strpos($url, 'mailto:', 0) !== false) {
            return false;
        }

        $childLink = $link->createChild($url);

        if (!$this->isLinkValid($childLink, $options)) {
            return false;
        }

        if ($this->isLinkInHierarchy($link, $childLink)) {
            return false;
        }

        $childLink->addSource($this->getName());

        return $link->addChildren($childLink);
    }


    /**
     * @param $childLink
     * @param array $options
     * @return bool
     */
    private function isLinkValid(Link $childLink, array $options)
    {

        // skip ignored paths urls
        if ($this->matchPatterns($childLink->getPath(), $options['ignored_path_patterns'])) {
            return false;
        }

        // skip ignored urls
        if ($this->matchPatterns($childLink->getUrl(), $options['ignored_url_patterns'])) {
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

        /** @var Link $existentLink */
        $existentLink = $this->entityManager->createQueryBuilder()
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
            ->getOneOrNullResult();

        if ($existentLink) {
            $existentLink->addSource($this->getName());

            $this->entityManager->persist($existentLink);
//            $this->entityManager->flush();
            return true;
        }

        return false;
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
