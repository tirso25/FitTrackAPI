<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class UsersController extends AbstractController
{
    #[Route('/seeAllUsers', 'api_seeAllUsers', methods: ['GET'])]
    public function seeAllUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(Users::class)->findAll();

        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id_usr' => $user->getIdUsr(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'password' => $user->getPassword(),
                'role' => $user->getRole()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }
}
