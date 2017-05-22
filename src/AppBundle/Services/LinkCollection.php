<?php

namespace AppBundle\Services;

use AppBundle\Entity\Link;
use Doctrine\Common\Collections\ArrayCollection;

class LinkCollection
{
    protected $links;

    /**
     * LinkCollection constructor.
     * @param array $links
     */
    public function __construct(array $links = [])
    {
        $this->links = new ArrayCollection($links);
    }

    /**
     * @param int $statusCode
     * @return ArrayCollection
     */
    public function getWithStatusCode($statusCode = 200)
    {
        return $this->links->filter(
            function (Link $link) use ($statusCode) {
                return $link->getStatusCode() === $statusCode;
            }
        );
    }

    /**
     * @return Link[]
     */
    public function getAll()
    {
        return $this->links->toArray();
    }

    /**
     * @return array
     */
    public function getAllFoundStatusCodes()
    {
        $statusCodes = [];

        $this->links->filter(
            function (Link $link) use (&$statusCodes) {
                array_push($statusCodes, $link->getStatusCode());
            }
        );

        return array_unique($statusCodes);
    }

    /**
     * @return bool|Link
     */
    public function getRoot()
    {
        return $this->links->isEmpty() ? false : $this->links->first()->getRoot();
    }
}
