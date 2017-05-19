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
//            set_time_limit(0);
            $urls = array_values(explode("\r\n", $form->get('urls')->getData()));

            $parser = $this->get('app.url_parser');
            foreach ($urls as $url) {
                $links[] = $link = new Link($url);

                $parser->parse($link);
                usleep(500);
            }

            return $this->render(
                'link/index.html.twig', [
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
