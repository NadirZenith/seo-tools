<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Link;
use AppBundle\Form\SimpleRunType;
use AppBundle\Services\HttpClient;
use AppBundle\Services\LinkCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/smoketest")
 */
class SmoketestController extends Controller
{
    /**
     * @Route("/")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {

        $form = $this->createForm(SimpleRunType::class, null);

        $form->handleRequest($request);

        $links = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $processor = $this->get('app.link_processor');
            foreach ($form->get('urls')->getData() as $url) {
                $links[] = $link = new Link($url);
//                $links[] = $link = new Link("http://www.schweppes.dev");
//                $links[] = $link = new Link("https://www.schweppes.es");

//                d(sprintf("start processing %s", $link->getUrl()));
                $processor->process($link);
            }

            return $this->render(
                'link/report.html.twig', [
                    'links' => new LinkCollection($links)
                ]
            );
        }

        return $this->render(
            'smoketest/index.html.twig', [
                'form'  => $form->createView(),
                'links' => new LinkCollection($links)
            ]
        );
    }

    /**
     * @Route("/dev")
     */
    public function devAction()
    {

        $client = $this->get('app.guzzle.client');
        $url = 'http://www.schweppes.dev/cocteleria/ginger';
        $url = 'http://www.schweppes.dev/es/historia.html';
        $response = $client->get($url);
        d($response->getHeaders());
        d($response->getStatusCode());
        d($response->getBody()->getContents());

        //        $redirectUriHistory = $response->getHeader(RedirectMiddleware::HISTORY_HEADER); // retrieve Redirect URI history
        //        $redirectCodeHistory = $response->getHeader('X-Guzzle-Redirect-Status-History'); // retrieve Redirect HTTP Status history
        //        array_push($redirectCodeHistory, $response->getStatusCode());

        //        d($redirectUriHistory);
        //        d($redirectCodeHistory);

        dd($client->getRedirects());

        d($response->getBody()->getContents());
    }
}
