<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GlobalService
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function validate($data)
    {
        $data = (string)($data ?? '');
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    //!!ELIMINAR EL JWT CON JS DESDE EL FRONT
    public function forceSignOut($entityManager, int $id_user, SessionInterface $session)
    {
        $this->userService->removeToken($entityManager, $id_user);

        setcookie("token", "", time() - 3600, "/");

        $session->remove('user_id');
    }
}
