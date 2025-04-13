<?php

namespace App\Controller;

use App\Entity\FavoriteExercises;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/FavoriteExercises')]
class FavoriteExercisesController extends AbstractController
{
    #[Route('/seeUserFavoriteExercises/{id<\d+>}', name: 'api_seeAllFavouritesExercises', methods: ['GET'])]
    public function seeAllFavouritesExercises(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();
        $id_user = $_SESSION["id_user"];

        $favourites = $entityManager->getRepository(FavoriteExercises::class)->findBy(['user' => $id]);

        if (!$favourites) {
            return $this->json(['type' => 'warning', 'message' => 'No users found'], Response::HTTP_OK);
        }

        $favUser = $entityManager->getRepository(FavoriteExercises::class)->findOneBy(['user' => $id]);

        if (!$favUser) {
            return $this->json(['type' => 'error', 'message' => 'This user does not have any favorites'], Response::HTTP_BAD_REQUEST);
        }

        if ($id_user !== $id) {
            return $this->json(['type' => 'error', 'message' => 'The user does not match'], Response::HTTP_BAD_REQUEST);
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

    #[Route('/addFavoriteExercise/{id<\d+>}', name: 'api_addFavoriteExercise', methods: ['POST'])]
    public function addExerciseFavourite(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();
        $id_user = $_SESSION["id_user"];

        $user = Users::getIdUser($id_user, $entityManager);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['type' => 'TEST', 'message' => 'TEST'], Response::HTTP_OK);
    }
}
