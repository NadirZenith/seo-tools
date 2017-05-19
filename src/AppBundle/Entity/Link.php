<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Link
 *
 * @ORM\Table(
 *     name="link",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="single_url_hierarchy", columns={"url", "root_id"})}
 * )
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LinkRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Link
{

    // link status
    const STATUS_PARSED = 'parsed';
    const STATUS_WAITING = 'waiting';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_ERROR = 'error';

    // link type
    const TYPE_EXTERNAL = 'external';
    const TYPE_INTERNAL = 'internal';
    const TYPE_SITEMAP = 'sitemap';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var array
     */
    private $parsedUrl;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="checked_at", type="datetime", nullable=true)
     */
    private $checkedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, nullable=true)
     */
    private $status = Link::STATUS_WAITING;

    /**
     * @var string
     *
     * @ORM\Column(name="status_message", type="string", length=255, nullable=true)
     */
    private $statusMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private $type = Link::TYPE_INTERNAL;

    /**
     * @var string
     *
     * @ORM\Column(name="status_code", type="string", length=255, nullable=true)
     */
    private $statusCode = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="response", type="text", nullable=true)
     */
    private $response;

    /**
     * @var string
     *
     * @ORM\Column(name="robots", type="text", nullable=true)
     */
    private $robots;

    /**
     * @var array
     *
     * @ORM\Column(name="response_headers", type="array", nullable=true)
     */
    private $responseHeaders;

    /**
     * @var array
     *
     * @ORM\Column(name="redirects", type="array", nullable=true)
     */
    private $redirects;

    /**
     * @var array
     *
     * @ORM\Column(name="metas", type="array", nullable=true)
     */
    private $metas;

    /**
     * @var array
     *
     * @ORM\Column(name="validation", type="array", nullable=true)
     */
    private $validation;

    /**
     * @var array
     *
     * @ORM\Column(name="raw_urls", type="array", nullable=true)
     */
    private $rawUrls;

    /**
     * @var Link
     *
     * @ORM\ManyToOne(targetEntity="Link", cascade={"persist"}, fetch="EAGER")
     */
    private $root;

    /**
     * @var Link
     *
     * @ORM\ManyToOne(targetEntity="Link", inversedBy="children", cascade={"persist"}, fetch="EAGER")
     */
    private $parent;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Link", mappedBy="parent", cascade={"persist", "remove"}, fetch="EAGER")
     */
    private $children;

    /**
     * Link constructor.
     *
     * @param null $url
     * @param string $type
     */
    public function __construct($url = null, $type = self::TYPE_INTERNAL)
    {
        $this->children = new ArrayCollection();
        $this->setUrl($url);
        $this->setType($type);

        $this->setRoot($this);
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        $this->parseUrl();
    }

    /**
     * @return array
     */
    public function getRedirects()
    {
        return $this->redirects;
    }

    /**
     * @param array $redirects
     */
    public function setRedirects($redirects)
    {
        $this->redirects = $redirects;
    }

    /**
     * @return bool
     */
    public function hasRedirects()
    {
        return !empty($this->redirects);
    }

    /**
     * Parse the current url
     */
    private function parseUrl()
    {
        $this->parsedUrl = parse_url($this->url);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return isset($this->url) ? $this->url : 'n/a';
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Link
     */
    public function setUrl($url)
    {
        $this->url = trim($url);
        $this->parseUrl();

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set checkedAt
     *
     * @param \DateTime $checkedAt
     *
     * @return Link
     */
    public function setCheckedAt($checkedAt)
    {
        $this->checkedAt = $checkedAt;

        return $this;
    }

    /**
     * Get checkedAt
     *
     * @return \DateTime
     */
    public function getCheckedAt()
    {
        return $this->checkedAt;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Link
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param string $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return array
     */
    public function getMetas()
    {
        return $this->metas;
    }

    /**
     * @param array $metas
     */
    public function setMetas(array $metas)
    {
        $this->metas = $metas;
    }

    /**
     *  Set meta by key
     *
     * @param  $key
     * @param  $value
     * @return $this
     */
    public function setMeta($key, $value)
    {
        $this->metas[$key] = $value;

        return $this;
    }

    /**
     * Get meta by key
     *
     * @param  $key
     * @return bool|mixed
     */
    public function getMeta($key)
    {
        return isset($this->metas[$key]) ? $this->metas[$key] : false;
    }

    /**
     * @return array
     */
    public function getRawUrls()
    {
        return $this->rawUrls;
    }

    /**
     * @param array $rawUrls
     */
    public function setRawUrls(array $rawUrls)
    {
        $this->rawUrls = $rawUrls;
    }

    /**
     * @return bool|string
     */
    public function getScheme()
    {
        return isset($this->parsedUrl['scheme']) ? $this->parsedUrl['scheme'] : false;
    }

    /**
     * @return bool|string
     */
    public function getHost()
    {
        return isset($this->parsedUrl['host']) ? $this->parsedUrl['host'] : false;
    }

    /**
     * @return bool|string
     */
    public function getPath()
    {
        return isset($this->parsedUrl['path']) ? $this->parsedUrl['path'] : false;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param ArrayCollection $children
     */
    public function setChildren(ArrayCollection $children)
    {
        $this->children = $children;
    }

    /**
     * @param Link $link
     * @return bool
     */
    public function addChildren(Link $link)
    {

        if ($this->containsLinkChildrenUrl($this, $link->getUrl())) {
            return false;
        }

        $link->setRoot($this->getRoot() ? $this->getRoot() : $this);
        $link->setParent($this);
        $this->getChildren()->add($link);

        return true;
    }

    /**
     * @return Link
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Link $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return Link
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param Link $root
     */
    public function setRoot(Link $root)
    {
        $this->root = $root;
    }

    /**
     * @param Link $link
     * @return array
     */
    private function getLinkChildrenUrls(Link $link)
    {
        $urls = [];
        if ($link) {
            foreach ($link->getChildren()->toArray() as $link) {
                array_push($urls, $link->getUrl());
            }
        }

        return $urls;
    }

    /**
     * @return array Array of childre urls
     */
    public function getChildrenUrls()
    {
        return $this->getLinkChildrenUrls($this);
    }

    /**
     * @param Link $link
     * @param string $url
     * @return bool
     */
    public function containsLinkChildrenUrl(Link $link, $url)
    {
        return $link->getChildren()->exists(
            function ($idx, $link) use ($url) {
                return $link->getUrl() === $url;
            }
        );
    }

    /**
     * @return array
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * @param array $validation
     */
    public function setValidation($validation)
    {
        $this->validation = $validation;
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }

    /**
     * @param string $statusMessage
     */
    public function setStatusMessage($statusMessage)
    {
        $this->statusMessage = $statusMessage;
    }

    /**
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * @param array $responseHeaders
     */
    public function setResponseHeaders($responseHeaders)
    {
        $this->responseHeaders = $responseHeaders;
    }

    /**
     * @return bool True if its a root link, False if its not
     */
    public function isRoot()
    {
        return $this->getId() === $this->getRoot()->getId();
    }

    /**
     * @return string
     */
    public function getRobots()
    {
        return $this->robots;
    }

    /**
     * @param string $robots
     */
    public function setRobots($robots)
    {
        $this->robots = $robots;
    }
}
