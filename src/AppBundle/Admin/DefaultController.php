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

    protected function createLinkListQueryBuilder($entityClass, $sortDirection, $sortField)
    {
        /**
         * @var QueryBuilder $queryBuilder
         */
        $queryBuilder = $this->getDoctrine()->getRepository($entityClass)->createQueryBuilder('l');

        $queryBuilder->where('l.parent is NULL');
        if ($parent = $this->request->get('parent')) {
            $queryBuilder->where('l.parent = :parent');

            $queryBuilder->setParameters(
                [
                    'parent' => $parent
                ]
            );
        } elseif ($root = $this->request->get('root')) {
            $queryBuilder->where('l.root = :root');

            $queryBuilder->setParameters(
                [
                    'root' => $root
                ]
            );
        }

        $queryBuilder->orderBy(sprintf('l.%s', $sortField), $sortDirection);
        $queryBuilder->setMaxResults(100000);

        return $queryBuilder;
    }

    protected function findAll($entityClass, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        if ($entityClass === Link::class) {
            $maxPerPage = 10000;
        }

        return parent::findAll($entityClass, $page, $maxPerPage, $sortField, $sortDirection, $dqlFilter);
    }
}
