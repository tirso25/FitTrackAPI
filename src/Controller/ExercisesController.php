<?php

namespace App\Controller;

use App\Entity\Exercises;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;


#[Route('/api/exercises')]
class ExercisesController extends AbstractController
{
    #[Route('/seeAllExercises', 'api_seeAllExercises', methods: ['GET'])]
    public function seeAllExercises(EntityManagerInterface $entityManager): JsonResponse
    {
        $exercises = $entityManager->getRepository(Exercises::class)->findAll();

        if (!$exercises) {
            return $this->json(['error' => 'No exercises found'], Response::HTTP_OK);
        }

        $data = [];

        foreach ($exercises as $excercise) {
            $data[] = [
                'id_exe' => $excercise->getIdExe(),
                'name' => $excercise->getName(),
                'description' => $excercise->getDescription(),
                'category' => $excercise->getCategory(),
                'likes' => $excercise->getLikes(),
                'active' => $excercise->getActive()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/seeOneExcercise/{id<\d+>}', 'api_seeOneExcercise', methods: ['GET'])]
    public function seeOneExcercise(EntityManagerInterface $entityManager, $id): JsonResponse
    {
        $excercise = $entityManager->find(Exercises::class, $id);

        if (!$excercise) {
            return $this->json(['error' => 'The excercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data[] = [
            'id_exe' => $excercise->getIdExe(),
            'name' => $excercise->getName(),
            'description' => $excercise->getDescription(),
            'category' => $excercise->getCategory(),
            'likes' => $excercise->getLikes(),
            'active' => $excercise->getActive()
        ];

        return $this->json($data, Response::HTTP_OK);
    }
}
