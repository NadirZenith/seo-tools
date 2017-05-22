<?php

namespace AppBundle\Admin;

use AppBundle\Entity\User;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;

class FosUserAdminController extends BaseAdminController
{

    /**
     * @return User
     */
    public function createNewEntity()
    {
        return $this->get('fos_user.user_manager')->createUser();
    }

    /**
     * @param User $user
     */
    public function prePersistEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
    }

    /**
     * @param User $user
     */
    public function preUpdateEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
    }
}
