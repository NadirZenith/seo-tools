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
                $processor->process($link, ['parsers' => 'html']);
            }

            return $this->render(
                'link/report.html.twig', [
                    'links' => new LinkCollection($links)
                ]
            );
        }

        $form->get('urls')->setData(['http://www.schweppes.dev', 'https://www.schweppes.es']);
        return $this->render(
            'smoketest/index.html.twig', [
                'form'  => $form->createView(),
                'links' => new LinkCollection($links)
            ]
        );
    }
}
