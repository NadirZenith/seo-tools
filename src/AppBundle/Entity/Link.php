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

    /**
     * Link status for processor
     */
    const STATUS_PARSED = 'parsed';
    const STATUS_WAITING = 'waiting';
    const STATUS_SKIPPED = 'skipped';
    const STATUS_ERROR = 'error';

    /**
     * Type of Link in relation with parent
     */
    const TYPE_EXTERNAL = 'external'; // link is in another domain
    const TYPE_INTERNAL = 'internal'; // lnk is in the same domain as parent

    /**
     * Source of this link
     */
    const SOURCE_HTML = 'html'; // comes from html (text/html)
    const SOURCE_SITEMAP = 'sitemap'; // comes from a sitemap (text/xml)
    const SOURCE_ROBOTS = 'robots'; // comes from a robot (text/plain)

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
     * @ORM\Column(name="source", type="string", length=255)
     */
    private $source = Link::SOURCE_HTML;

    /**
     * @var string
     *
     * @ORM\Column(name="source2", type="string", nullable=true)
     */
    private $source2;

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
    private $metas = [
        'response_headers' => null,
        'link_urls'        => null
    ];

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
     * @var array
     *
     * @ORM\Column(name="raw_imgs", type="array", nullable=true)
     */
    private $rawImgs;

    /**
     * @var Link
     *
     * @ORM\ManyToOne(targetEntity="Link", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(onDelete="CASCADE") // allow delete root rows
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
     * @param string $source
     * @internal param string $type
     */
    public function __construct($url = null, $source = self::SOURCE_HTML)
    {
        $this->children = new ArrayCollection();
        $this->setUrl($url);
        $this->setSource($source);

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
//        $this->url = trim($url);
        $this->url = trim($url, "\t\n\r\0/");

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
//        if ($link) {
        foreach ($link->getChildren()->toArray() as $link) {
            array_push($urls, $link->getUrl());
//            }
        }

        return $urls;
    }

    /**
     * @return array Array of children urls
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
     * @param null $key
     * @return array|false|string
     */
    public function getResponseHeader($key = null)
    {
        if (!isset($this->responseHeaders[$key])) {
            return false;
        }

        return count($this->responseHeaders[$key]) > 1 ? $this->responseHeaders[$key] : reset($this->responseHeaders[$key]);
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

    /**
     * @return array
     */
    public function getRawImgs()
    {
        return $this->rawImgs;
    }

    /**
     * @param array $rawImgs
     */
    public function setRawImgs($rawImgs)
    {
        $this->rawImgs = $rawImgs;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @param string $url
     * @return Link
     */
    public function createChild($url)
    {
        $link = new self($url, $this->getSource());

        // check if child url is relative and has a path (ex: is not a #hash url)
        if (!$link->getHost() && $link->getPath()) {
            // child link url is relative, prepend link scheme and host

            $link->setUrl(sprintf("%s://%s/%s", $this->getScheme(), $this->getHost(), $link->getPath()))
                ->setType(Link::TYPE_INTERNAL);
        }

        if ($this->getHost() !== $link->getHost()) {
            $link->setType(Link::TYPE_EXTERNAL);
        }

        return $link;
    }

    public function addSource($source)
    {
        $sources = explode('|', $this->source2);

        array_push($sources, $source);

        $this->source2 = implode('|', $sources);
    }

    /**
     * @return string
     */
    public function getSource2()
    {
        return $this->source2;
    }

    /**
     * @param string $source2
     */
    public function setSource2($source2)
    {
        $this->source2 = $source2;
    }
}
