<?php

namespace App\Controller;

use App\Entity\Test;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api', name: 'app_api_seeAll', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $usuariosApi = $entityManager->getRepository(Test::class)->findAll();

        $data = [];

        foreach ($usuariosApi as $usuario) {
            $data[] = [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
            ];
        }

        return $this->json($data, 200);
    }
}