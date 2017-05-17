<?php

namespace AppBundle\Admin;

use AppBundle\Entity\Link;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class DefaultController extends BaseAdminController
{

    public function crawlAction()
    {
        dd('here');
        // redirect to the 'list' view of the given entity
        return $this->redirectToRoute(
            'easyadmin', array(
                'action' => 'list',
                'entity' => $this->request->query->get('entity'),
            )
        );

        return $this->render(
            'AppBundle:Admin:crawl.html.twig', array(// ...
            )
        );
    }

    protected function createLinkListQueryBuilder($entityClass, $sortDirection, $sortField, $dqlFilter)
    {
        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->getDoctrine()->getRepository($entityClass)->createQueryBuilder('l');

        if ($parent = $this->request->get('parent')) {
            $qb->where('l.parent = :parent');

            $qb->setParameters(
                [
                    'parent' => $parent
                ]
            );
        } elseif ($root = $this->request->get('root')) {
            $qb->where('l.root = :root');

            $qb->setParameters(
                [
                    'root' => $root
                ]
            );
        } else {
            $qb->where('l.parent is NULL');
        }

        $qb->orderBy('l.id', $sortDirection);
        $qb->setMaxResults(100000);

        return $qb;

        d($qb->getQuery()->getArrayResult());
        dd(func_get_args());
    }

    protected function findAll($entityClass, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        if ($entityClass === Link::class) {
            $maxPerPage = 10000;
        }
        //        dd($this->config);
        return parent::findAll($entityClass, $page, $maxPerPage, $sortField, $sortDirection, $dqlFilter);
    }
}
