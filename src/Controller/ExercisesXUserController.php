<?php

namespace App\Controller;

use App\Entity\ExercisesXUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/exercisesXuser')]
class ExercisesXUserController extends AbstractController
{
    #[Route('/seeAllFavouritesExercises/{id<\d+>}', name: 'api_seeAllFavouritesExercises', methods: ['GET'])]
    public function seeAllFavouritesExercises(EntityManagerInterface $entityManager, int $id): JsonResponse
    {

        $favourites = $entityManager->getRepository(ExercisesXUser::class)->findBy(['user' => $id]);

        if (!$favourites) {
            return $this->json(['type' => 'warning', 'message' => 'No users found'], Response::HTTP_OK);
        }

        $data = [];

        foreach ($favourites as $favourite) {
            $data[] = [
                'id_exe' => $favourite->getExercise()->getIdExe(),
                'name_exe' => $favourite->getExercise()->getName(),
                'description_exe' => $favourite->getExercise()->getDescription(),
                'category_exe' => $favourite->getExercise()->getCategory()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/addExerciseFavourite/{id<\d+>}', name: 'api_addExerciseFavourite', methods: ['POST'])]
    public function addExerciseFavourite(EntityManagerInterface $entityManager, int $id) {}
}
