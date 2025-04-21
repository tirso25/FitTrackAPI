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

#[Route('/api/favoriteExercises')]
class FavoriteExercisesController extends AbstractController
{
    private function forceSignOut(EntityManagerInterface $entityManager, int $id_user)
    {
        Users::removeToken($entityManager, $id_user);

        setcookie("token", "", time() - 3600, "/");

        unset($_SESSION['id_user']);
    }

    #[Route('/seeFavoriteExercises', name: 'api_seeFavoriteExercises', methods: ['GET'])]
    public function seeAllFavouritesExercises(EntityManagerInterface $entityManager): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $favourites = FavoriteExercises::getFavouriteExercises($_SESSION['id_user'], $entityManager);

        return $this->json($favourites, Response::HTTP_OK);
    }

    #[Route('/addFavoriteExercise/{id<\d+>}', name: 'api_addFavoriteExercise', methods: ['POST'])]
    public function addExerciseFavourite(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $user = Users::getIdUser($_SESSION['id_user'], $entityManager);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class, $_SESSION['id_user']);

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
        $newFavourite->setActive(true);

        $entityManager->persist($newFavourite);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Exercise added to favorite correctly'], Response::HTTP_OK);
    }

    #[Route('/undoFavorite/{id<\d+>}', name: 'api_undoFavorite', methods: ['DELETE', 'POST'])]
    public function undoFavorite(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $favourite =  $entityManager->getRepository(FavoriteExercises::class)->findOneBy(['user' => $_SESSION['id_user'], 'exercise' => $id]);

        if (!$favourite) {
            return $this->json(['type' => 'error', 'message' => 'You have not added this exercise to your favorites or this exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($favourite);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Exercise successfully removed from favorites'], Response::HTTP_OK);
    }
}
