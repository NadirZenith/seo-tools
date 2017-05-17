<?php

namespace AppBundle\Services;

use AppBundle\Entity\Link;
use Doctrine\Common\Collections\ArrayCollection;

class LinkCollection
{
    protected $links;

    public function __construct(array $links = array())
    {
        $this->links = new ArrayCollection($links);
    }

    public function getWithStatusCode($statusCode = 200)
    {
        return $this->links->filter(function (Link $link) use ($statusCode) {
            return $link->getStatusCode() === $statusCode;
        });
    }

    public function getAll()
    {
        return $this->links->toArray();
    }

    public function getAllFoundStatusCodes()
    {
        $statusCodes = [];

        $this->links->filter(function (Link $link) use (&$statusCodes) {
            if (!in_array($link->getStatusCode(), $statusCodes)) {
                array_push($statusCodes, $link->getStatusCode());
            }
        });

        return $statusCodes;
    }
}
