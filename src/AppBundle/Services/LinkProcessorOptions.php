<?php

namespace AppBundle\Services;

class LinkProcessorOptions
{

    private $options = [
        'ignored_url_patterns' => array(),
        'ignored_path_patterns' => array()
    ];

    /**
     * UrlParserOptions constructor.
     *
     * @param $options
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge($this->options, $options);
    }

    public function getIgnoredUrlPatterns()
    {
        return is_array($this->options['ignored_url_patterns']) ? $this->options['ignored_url_patterns'] : [$this->options['ignored_url_patterns']];
    }

    public function getIgnoredPathPatterns()
    {
        return is_array($this->options['ignored_path_patterns']) ? $this->options['ignored_path_patterns'] : [$this->options['ignored_path_patterns']];
    }
}
