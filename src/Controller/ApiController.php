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
            'message' => 'API de FitTrack',
        ];

        return $this->json($data);
    }
}
