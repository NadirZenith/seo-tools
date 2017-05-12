<?php

namespace AppBundle\Admin;

use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class DefaultController extends BaseAdminController
{

    public function crawlAction()
    {
        dd('here');
        // redirect to the 'list' view of the given entity
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));

        return $this->render('AppBundle:Admin:crawl.html.twig', array(// ...
        ));
    }

    protected function createLinkListQueryBuilder($entityClass, $sortDirection, $sortField, $dqlFilter)
    {
//        dd('xau');
        /** @var QueryBuilder $qb */
        $qb = $this->getDoctrine()->getRepository($entityClass)->createQueryBuilder('l');

        $qb->where('l.parent is NULL');

        $qb->orderBy('l.id', $sortDirection);

        return $qb;

        d($qb->getQuery()->getArrayResult());
        dd(func_get_args());
    }

}
