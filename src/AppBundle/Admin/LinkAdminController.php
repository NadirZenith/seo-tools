<?php

namespace AppBundle\Admin;

use Doctrine\ORM\QueryBuilder;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Component\Routing\Annotation\Route;

class LinkAdminController extends BaseAdminController
{

    /**
     * @param string $entityClass
     * @param string $sortDirection
     * @param null $sortField
     * @param null $dqlFilter
     * @return QueryBuilder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) ($dqlFilter)
     */
    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null)
    {
        /**
         * @var QueryBuilder $queryBuilder
         */
        $queryBuilder = $this->getDoctrine()->getRepository($entityClass)->createQueryBuilder('l');

        $queryBuilder->where('l.parent is NULL');
//        if ('children' === $this->request->get('action')) {
//            $queryBuilder->where('l.root = :root')->setParameter('root', $this->request->get('id'));
//        } else {
        if ($parent = $this->request->get('parent')) {
            $queryBuilder->where('l.parent = :parent')->setParameter('parent', $parent);
        } elseif ($root = $this->request->get('root')) {
            $queryBuilder->where('l.root = :root')->setParameter('root', $root);
        }
//        }

        $queryBuilder->orderBy(sprintf('l.%s', $sortField), $sortDirection);
        $queryBuilder->setMaxResults(100000);

        return $queryBuilder;
    }

    protected function findAll($entityClass, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        $maxPerPage = 10000;
        return parent::findAll($entityClass, $page, $maxPerPage, $sortField, $sortDirection, $dqlFilter);
    }
}
