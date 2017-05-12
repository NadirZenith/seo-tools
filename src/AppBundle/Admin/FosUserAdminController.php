<?php

namespace AppBundle\Admin;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class FosUserAdminController extends BaseAdminController
{

    public function createNewEntity()
    {
        return $this->get('fos_user.user_manager')->createUser();
    }

    public function prePersistEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
    }

    public function preUpdateEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
    }

}
