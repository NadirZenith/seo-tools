<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Link;
use AppBundle\Form\SimpleRunType;
use AppBundle\Services\UrlParser;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Session;
use Buzz\Browser;
use Buzz\Message\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/index/{slug}", name="index_test", defaults={"slug": false})
     * @param null $slug
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexTestAction($slug = null)
    {
        // replace this example code with whatever you need
        return $this->render('default/index_test.html.twig', ['slug' => $slug]);
    }

    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function smokeTestAction(Request $request)
    {
        $testUrls = array(
            'https://www.schweppes.es/tonica/nuestras-tonicas/classic/soda',
            'https://www.schweppes.es/cocteleria/jigger-cuchara-imperial-y-abridor-como-se-usan-correctamente'
        );

        $testUrls = array();

        $form = $this->createForm(
            SimpleRunType::class, null, array(
                'test_data' => implode("\n", $testUrls)
            )
        );

        $form->handleRequest($request);

        $status = array();
        $parsedUrls = array();
        if ($form->isSubmitted() && $form->isValid()) {
            set_time_limit(0);
            $data = $form->getData();
            $urls = array_values(explode("\r\n", $data['urls']));


            //            $br = new \Buzz\Message\Request();
            //            $br->s
            /**
             * @var Browser $client
             */
            $browser = $this->get('buzz');
            $browser->getClient()->setTimeout(5000);
            foreach ($urls as $url) {
                $url = trim($url);
                /**
                 * @var Response $response
                 */
                $response = $browser->get($url);
                $status[$response->getStatusCode()][] = $url;

                $parsedUrls[] = $url;
                usleep(500);
            }
        }

        // replace this example code with whatever you need
        return $this->render(
            'default/index.html.twig', [
                'form' => $form->createView(),
                'status' => $status,
                'urls' => $parsedUrls
            ]
        );
    }

    /**
     * @Route("/discover", name="discover_homepage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function discoverAction(Request $request)
    {
        $form = $this->createFormBuilder(new Link(), array())
            ->add('url', UrlType::class)
            ->add(
                'submit', SubmitType::class, array(
                    'label' => 'form.label.submit'
                )
            )
            ->getForm();
        $form->handleRequest($request);

        $filePath = false;
        if ($form->isSubmitted() && $form->isValid()) {
            set_time_limit(0);
            /**
             * @var Link $link
             */
            $link = $form->getData();

            /**
             * @var Browser $client
             */
            $browser = $this->get('buzz');
            $browser->getClient()->setTimeout(5000);
            /**
             * @var Response $response
             */
            $response = $browser->get($link->getUrl());

            $link->setResponse($response->getContent());
            $link->setStatusCode($response->getStatusCode());


            $driver = 'firefox';
            $driver = 'chrome';
            $driver = 'phantomjs';
            $sdriver = new Selenium2Driver($driver);
            $sbrowser = new Session($sdriver);


            $sbrowser->start();

            $sbrowser->resizeWindow(1280, 1024);

            $cookieArray = array(
                'domain' => '.schweppes.dev',
                'path' => '/',
                'name' => 'allowAdultContent',
                'value' => '1',
                'secure' => false, // thanks, chibimagic!
            );
            //            $sbrowser->setCookie('allowAdultContent', '1'); //only works? if we fetch an url first(can only set cookies for the current domain)
            $sdriver->getWebDriverSession()->setCookie($cookieArray);

            $sbrowser->visit($link->getUrl());
            $sbrowser->wait(3000);

            $sbrowser->getPage();

            $path = $this->getParameter('kernel.root_dir') . '/../web/uploads/';
            $file = 'screenshot.png';
            $filePath = $path . $file;

            file_put_contents($filePath, $sdriver->getScreenshot());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($link);
            $manager->flush();
        }

        // replace this example code with whatever you need
        return $this->render(
            'default/discover.html.twig', [
                'form' => $form->createView(),
                'link' => $form->getData(),
                'screenshot' => $filePath
            ]
        );
    }

    /**
     * @Route("/seo-report2", name="seo_report2")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function seoReport2Action(Request $request)
    {

        $form = $this->createFormBuilder(new Link(), array())
            ->add('url', UrlType::class)
            ->add(
                'submit', SubmitType::class, array(
                    'label' => 'form.label.submit'
                )
            )
            ->getForm();

        $form->handleRequest($request);

        $filePath = false;
        if ($form->isSubmitted() && $form->isValid()) {
            set_time_limit(0);
            /**
             * @var Link $link
             */
            $link = $form->getData();

            $driver = 'firefox';
            $driver = 'chrome';
            //            $driver = 'phantomjs';
            $sdriver = new Selenium2Driver($driver);
            $sbrowser = new Session($sdriver);

            $sbrowser->start();
            $sbrowser->resizeWindow(1280, 1024);

            switch ($driver) {
                case 'phantomjs':
                    $cookieArray = array(
                    'domain' => '.schweppes.dev',
                    'path' => '/',
                    'name' => 'allowAdultContent',
                    'value' => '1',
                    'secure' => false, // thanks, chibimagic!
                        );
                        $sdriver->getWebDriverSession()->setCookie($cookieArray);
                    break;
                case 'chrome':
                    $sbrowser->visit($link->getUrl());
                    $sbrowser->wait(1000);
                    $sbrowser->setCookie('allowAdultContent', '1'); //only works? if we fetch an url first(can only set cookies for the current domain)
                    break;
            }

            $sbrowser->visit($link->getUrl());
            $sbrowser->wait(3000);

            $page = $sbrowser->getPage();
            d($page);
            d($sdriver);
            d($sdriver->getWebDriverSession());
            dd($sbrowser);

            $link->setResponse($page->getContent());
            $link->setStatusCode($sdriver->getStatusCode());

            //            $path = $this->getParameter('kernel.root_dir') . '/../web/uploads/';
            //            $file = 'screenshot.png';
            //            $file_path = $path . $file;
            //            file_put_contents($file_path, $sdriver->getScreenshot());


            $manager = $this->getDoctrine()->getManager();
            $manager->persist($link);
            $manager->flush();
        }

        // replace this example code with whatever you need
        return $this->render(
            'default/discover.html.twig', [
                'form' => $form->createView(),
                'link' => $form->getData(),
                'screenshot' => $filePath
            ]
        );
    }

    /**
     * @Route("/seo-report", name="seo_report")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function seoReportAction(Request $request)
    {

        $form = $this->createFormBuilder(new Link(), array())
            ->add('url', UrlType::class)
            ->add(
                'submit', SubmitType::class, array(
                    'label' => 'form.label.submit'
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            set_time_limit(0);
            /**
             * @var Link $link
             */
            $link = $form->getData();

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($link);
            $manager->flush();
        }

        // replace this example code with whatever you need
        return $this->render(
            'default/discover.html.twig', [
                'form' => $form->createView(),
                'link' => $form->getData(),
            ]
        );
    }

    /**
     * @Route("/seo-report-run", name="seo_report_run")
     */
    public function seoReportRunAction()
    {

        /**
         * @var UrlParser $parser
         */
        $parser = $this->get('app.url_parser');
        $manager = $this->getDoctrine()->getManager();
        $links = $manager->getRepository(Link::class)->findBy(['status' => Link::STATUS_WAITING]);

        /**
         * @var Link $link
         */
        foreach ($links as $k => $link) {
            d(sprintf('%d. Start parsing url %s', ++$k, $link->getUrl()));
            $parser->parse(
                $link, [
                    'ignore_patterns' => '/^\/\_/'
                ]
            );

            d(sprintf(" - status: %d", $link->getStatusCode()));
            d(sprintf("Found %d new urls \n    %s", count($link->getChildrenUrls()), implode("\n    ", $link->getChildrenUrls())));

            $manager->persist($link);
            $manager->flush();
        }
        dd($links);
    }
}
