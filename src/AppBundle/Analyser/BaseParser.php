<?php
/**
 * Created by PhpStorm.
 * User: tino
 * Date: 5/23/17
 * Time: 7:20 PM
 */

namespace AppBundle\Analyser;

use AppBundle\Entity\Link;
use AppBundle\Entity\LinkSource;
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
     * @return bool|Link
     */
    protected function createLinkChildren(Link $link, $url, array $options)
    {
        // ignore mail urls
        if (strpos($url, 'mailto:', 0) !== false) {
            return false;
        }

        $childLink = $link->createChild($url);

        // ignore from patterns && validate
        if (!$this->isLinkValid($childLink, $options)) {
            return false;
        }

        // check(& hit) index(url, root) constraint
        if ($this->isLinkInHierarchy($link, $childLink)) {
            return false;
        }

        $childLink->addSource($this->getSource($this->getName()));
        return $link->addChildren($childLink) ? $childLink : false;
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
            // add source
            $existentLink->addSource($this->getSource($this->getName()));

            $this->entityManager->persist($existentLink);
//            $this->entityManager->flush($existentLink);
            return true;
        }

        return false;
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

    private function getSource($name)
    {
        /** @var LinkSource $source */
        $source = $this->entityManager->createQueryBuilder()
            ->select('ls')
            ->from(LinkSource::class, 'ls')
            ->where('ls.source = :source')
            ->setParameter('source', $name)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$source) {
            $source = new LinkSource($name);
            $this->entityManager->persist($source);
            // must save now for the next link find it
            $this->entityManager->flush($source);
        }

        return $source;
    }
}
