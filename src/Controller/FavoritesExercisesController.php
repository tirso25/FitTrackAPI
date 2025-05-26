<?php

namespace App\Controller;

use App\Entity\Exercises;
use App\Entity\FavoritesExercises;
use App\Entity\Users;
use App\Service\ExerciseService;
use App\Service\FavoritesExercisesService;
use App\Service\GlobalService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

//!VER NUEVOS CAMBIOS CON PUBLIC (perfil publico)

#[Route('/api/favoriteExercises')]
class FavoritesExercisesController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private GlobalService $globalService,
        private FavoritesExercisesService $favoriteExercisesService,
        private ExerciseService $exerciseService,
    ) {}

    #[Route('/seeFavoritesExercises', name: 'api_seeFavoritesExercises', methods: ['GET'])]
    public function seeAllFavouritesExercises(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\Users $thisuser */
        $thisuser = $this->getUser();
        $thisuserId = $thisuser->getUserId();
        $thisuserStatus = $thisuser->getStatus();

        if (!$thisuser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuserStatus !== 'active') {
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        $favourites = $this->favoriteExercisesService->getFavouriteExercises($thisuserId, $entityManager);

        return $this->json($favourites, Response::HTTP_OK);
    }

    #[Route('/addFavoriteExercise/{id<\d+>}', name: 'api_addFavoriteExercise', methods: ['POST'])]
    public function addExerciseFavourite(EntityManagerInterface $entityManager, int $id, SessionInterface $session): JsonResponse
    {
        $idUser = $session->get('user_id');

        if (!$idUser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userService->checkState($entityManager, $idUser) !== "active") {
            $this->globalService->forceSignOut($entityManager, $idUser, $session);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class, $idUser);

        $exercise = $this->exerciseService->isActive($id, $entityManager);

        if (!$exercise) {
            return $this->json(['type' => 'error', 'message' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $thisExercise = $entityManager->find(Exercises::class, $id);

        $existing = $entityManager->getRepository(FavoritesExercises::class)->findOneBy(['user' => $thisUser, 'exercise' => $thisExercise]);

        if ($existing) {
            return $this->json(['type' => 'warning', 'message' => 'Exercise already added to favorite'], Response::HTTP_BAD_REQUEST);
        }

        $newFavourite = new FavoritesExercises();

        $newFavourite->setUser($thisUser);
        $newFavourite->setExercise($thisExercise);
        $newFavourite->setActive(true);

        $entityManager->persist($newFavourite);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Exercise added to favorite correctly'], Response::HTTP_OK);
    }

    #[Route('/undoFavorite/{id<\d+>}', name: 'api_undoFavorite', methods: ['DELETE'])]
    public function undoFavorite(EntityManagerInterface $entityManager, int $id, SessionInterface $session): JsonResponse
    {
        $idUser = $session->get('user_id');

        if (!$idUser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userService->checkState($entityManager, $idUser) !== "active") {
            $this->globalService->forceSignOut($entityManager, $idUser, $session);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $favourite =  $entityManager->getRepository(FavoritesExercises::class)->findOneBy(['user' => $idUser, 'exercise' => $id]);

        if (!$favourite) {
            return $this->json(['type' => 'error', 'message' => 'You have not added this exercise to your favorites or this exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($favourite);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Exercise successfully removed from favorites'], Response::HTTP_OK);
    }
}
