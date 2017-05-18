<?php

namespace AppBundle\Admin;

use Doctrine\ORM\QueryBuilder;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Component\Routing\Annotation\Route;

class LinkAdminController extends BaseAdminController
{

//    /**
//     * @Route("/admin/delete_element/{id}", name="admin_link_children")
//     */
//    public function childrenAction()
//    {
//        unset($this->entity['list']['actions']['children']);
//        $fields = $this->entity['list']['fields'];
////        dump($this);die;
//        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);
//        return $this->render($this->entity['templates']['list'], array(
//            'paginator' => $paginator,
//            'fields' => $fields,
//            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
//        ));
//
//    }

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
