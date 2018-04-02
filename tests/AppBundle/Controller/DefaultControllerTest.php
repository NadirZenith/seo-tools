<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Link;
use AppBundle\Entity\LinkSource;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

if(class_exists('Kint')){
    function dd()
    {
        \Kint::dump(func_get_args());
        exit;
    }
    \Kint::$aliases[] = 'dd';
}

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {

        $sourceHtml = new LinkSource(LinkSource::SOURCE_HTML);
        $sourceSitemap= new LinkSource(LinkSource::SOURCE_SITEMAP);
        $sourceRobots = new LinkSource(LinkSource::SOURCE_ROBOTS);
        $root = new Link('http://clubber-mag.test', $sourceHtml);
//        dd($root);

        $this->assertContains($sourceHtml, $root->getSources());
//        $redirect = new Link('http://www.clubber-mag.test', $sourceHtml);

        $redirect = $root->createChild('http://www.clubber-mag.test');

        dd($redirect->getType());
        dd(
          $root->getHost(),
          $redirect->getHost()
        );
        $this->assertTrue(true);
        $this->assertFalse(false);
//        $this->assertEquals(200, $client->getResponse()->getStatusCode());
//        $this->assertContains('Welcome to Symfony', $crawler->filter('#container h1')->text());
    }
}
