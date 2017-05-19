<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Link;
use AppBundle\Services\LinkCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LinkController extends Controller
{

    /**
     * @Route("/admin/link-parse/{id}", name="admin_link_parse", requirements={"id": "\d+"})
     * @param $id
     */
    public function parseAction($id)
    {
        $parser = $this->get('app.url_parser');
        $link = $this->getLink($id);

        d($link);
        $parser->parse($link);
        dd($link);
    }

    /**
     * @Route("/admin/link-report", name="admin_link_report")
     */
    public function reportAction(Request $request)
    {
        $links = $this->getDoctrine()->getRepository(Link::class)->findBy(['root' => $request->get('id')]);

        return $this->render(
            'link/index.html.twig', [
                'links' => new LinkCollection($links)
            ]
        );
    }

    /**
     * @Route("/admin/link-hierarchy", name="admin_link_hierarchy")
     * @param Request $request
     * @return Response
     */
    public function hierarchyAction(Request $request)
    {
//        $links = $this->getDoctrine()->getRepository(Link::class)->getHierarchicalFromRoot($request->get('id'));
        $link = $this->getDoctrine()->getRepository(Link::class)->find($request->get('id'));

        return $this->render(
            'link/hierarchical.html.twig', [
                'root' => $link
            ]
        );
    }

    /**
     * @Route("/admin/link-iframe-content/{id}", name="admin_link_iframe_content", requirements={"id": "\d+"})
     *
     * @param $id
     * @return Response
     * @internal param Request $request
     */
    public function linkContentAction($id)
    {
        $link = $this->getLink($id);

        return new Response($link->getResponse());
    }

    /**
     * @param $id
     * @return Link|null|object
     */
    private function getLink($id)
    {
        $link = $this->getDoctrine()->getRepository(Link::class)->find($id);

        if (!$link) {
            throw $this->createNotFoundException(sprintf("Link does not exist"));
        }

        return $link;
    }
}
