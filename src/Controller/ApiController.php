<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api', name: 'api_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $data = [
            'message' => '¡Bienvenido a mi API REST básica!',
            'status' => 'success',
            'data' => [
                'version' => '1.0',
                'author' => 'Tirmo05',
            ],
        ];

        return $this->json($data);
    }
}
