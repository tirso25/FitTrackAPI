<?php

namespace App\Controller;

use App\Entity\Excercises;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;


#[Route('/api')]
class ExcercisesController extends AbstractController
{
    #[Route('/seeAllExcercises', 'api_seeAllExcercises', methods: ['GET'])]
    public function seeAllExcercises(EntityManagerInterface $entityManager): JsonResponse
    {
        $excercises = $entityManager->getRepository(Excercises::class)->findAll();

        if (!$excercises) {
            return $this->json(['error' => 'No excercises found'], Response::HTTP_OK);
        }

        $data = [];

        foreach ($excercises as $excercise) {
            $data[] = [
                'id_exe' => $excercise->getIdExe(),
                'name' => $excercise->getName(),
                'description' => $excercise->getDescription(),
                'category' => $excercise->getCategory(),
                'likes' => $excercise->getLikes(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/seeOneExcercise/{id<\d+>}', 'api_seeOneExcercise', methods: ['GET'])]
    public function seeOneExcercise(EntityManagerInterface $entityManager, $id): JsonResponse
    {
        $excercise = $entityManager->find(Excercises::class, $id);

        if (!$excercise) {
            return $this->json(['error' => 'The excercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data[] = [
            'id_exe' => $excercise->getIdExe(),
            'name' => $excercise->getName(),
            'description' => $excercise->getDescription(),
            'category' => $excercise->getCategory(),
            'likes' => $excercise->getLikes()
        ];

        return $this->json($data, Response::HTTP_OK);
    }
}
