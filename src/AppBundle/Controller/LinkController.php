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
     * @Route("/admin/children", name="admin_link_children")
     */
    public function childrenAction(Request $request)
    {
        $links = $this->getDoctrine()->getRepository(Link::class)->findBy(['root' => $request->get('id')]);

        return $this->render(
            'link/index.html.twig', [
//                'form' => $form->createView(),
                'links' => new LinkCollection($links)
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
        $link = $this->getDoctrine()->getRepository(Link::class)->find($id);

        if (!$link || !$link->getResponse()) {
            throw $this->createNotFoundException(sprintf("Link does not exist or have empty content"));
        }

        return new Response($link->getResponse());
    }
}
