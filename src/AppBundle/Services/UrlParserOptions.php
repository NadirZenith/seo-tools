<?php
/**
 * Created by PhpStorm.
 * User: tino
 * Date: 5/11/17
 * Time: 6:49 PM
 */

namespace AppBundle\Services;


class UrlParserOptions
{

    private $options;

    /**
     * UrlParserOptions constructor.
     * @param $options
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge([
            'ignore_patterns' => array()
        ], $options);
    }

    public function getIgnoredUrlsPatterns()
    {
        return is_array($this->options['ignore_patterns']) ? $this->options['ignore_patterns'] : [$this->options['ignore_patterns']];
    }
}