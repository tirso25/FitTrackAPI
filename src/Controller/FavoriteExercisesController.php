<?php

namespace App\Controller;

use App\Entity\Exercises;
use App\Entity\FavoriteExercises;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

//!VER NUEVOS CAMBIOS CON PUBLIC (perfil publico)

#[Route('/api/FavoriteExercises')]
class FavoriteExercisesController extends AbstractController
{
    //!VER SI SI QUITA YA QUE ESTO YA LO HACE seeOneUser (CONTROLADOR DE USERS)
    #[Route('/seeUserFavoriteExercises/{id<\d+>}', name: 'api_seeAllFavouritesExercises', methods: ['GET'])]
    public function seeAllFavouritesExercises(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        // if ($id_user !== $id) {
        //     return $this->json(['type' => 'error', 'message' => 'The user does not match'], Response::HTTP_BAD_REQUEST);
        // }

        $favourites = FavoriteExercises::getFavouriteExercisesByUserId($id, $entityManager);

        if (empty($favourites)) {
            return $this->json(['type' => 'error', 'message' => 'This user does not exist or does not have favorites'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($favourites, Response::HTTP_OK);
    }

    #[Route('/addFavoriteExercise/{id<\d+>}', name: 'api_addFavoriteExercise', methods: ['POST'])]
    public function addExerciseFavourite(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();
        $id_user = $_SESSION['id_user'];

        if (!$id_user) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        $user = Users::getIdUser($id_user, $entityManager);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class, $id_user);

        $exercise = Exercises::isActive($id, $entityManager);

        if (!$exercise) {
            return $this->json(['type' => 'error', 'message' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $thisExercise = $entityManager->find(Exercises::class, $id);

        $existing = $entityManager->getRepository(FavoriteExercises::class)->findOneBy(['user' => $thisUser, 'exercise' => $thisExercise]);

        if ($existing) {
            return $this->json(['type' => 'warning', 'message' => 'Exercise already added to favorite'], Response::HTTP_BAD_REQUEST);
        }

        $newFavourite = new FavoriteExercises();

        $newFavourite->setUser($thisUser);
        $newFavourite->setExercise($thisExercise);

        $entityManager->persist($newFavourite);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Exercise added to favorite correctly'], Response::HTTP_OK);
    }
}
