<?php

namespace App\Controller;

use App\Entity\Exercises;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/addExercise', 'api_addExercise', methods: ['POST'])]
    public function addExercise(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $name = Exercises::validate(strtolower($data['name']));
        $description = Exercises::validate(strtolower($data['description']));
        $category = Exercises::validate($data['category']);

        $name_regex = "/^[a-z0-9]{1,30}$/";
        $category_regex = "/^[a-z0-9]{1,10}$/";

        if (empty($name) || empty($description) || empty($category)) {
            return $this->json(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match($name_regex, $name)) {
            return $this->json(['error' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($description) > 500 || strlen($description) < 10) {
            return $this->json(['error' => 'Invalid description format'], Response::HTTP_BAD_REQUEST);
        }

        if (Exercises::exerciseExisting($name, $entityManager)) {
            return $this->json(['error' => 'Exercise already exists'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($category, ['CHEST', 'SHOULDER', 'TRICEPS', 'BACK', 'BICEPS', 'ABDOMINALS', 'FEMORAL', 'QUADRICEPS', 'CALVES']) || !preg_match($category_regex, $category)) {
            return $this->json(['error' => 'Invalid category'], Response::HTTP_BAD_REQUEST);
        }

        $exercise = new Exercises();
        $exercise->setName($name);
        $exercise->setDescription($description);
        $exercise->setCategory($category);
        $exercise->setLikes(0);
        $exercise->setActive(true);

        $entityManager->persist($exercise);
        $entityManager->flush();

        return $this->json(['success' => 'Exercise successfully created'], Response::HTTP_CREATED);
    }

    #[Route('/deleteExercise/{id<\d+>}', 'api_deleteExercise', methods: ['DELETE', 'POST'])]
    public function deleteExercise(EntityManagerInterface $entityManager, $id): JsonResponse
    {
        $delexercise = $entityManager->find(Exercises::class, $id);

        if (!$delexercise) {
            return $this->json(['error' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $delexercise->setActive(false);
        $entityManager->flush();

        return $this->json(['success' => 'Exercise successfully deleted'], Response::HTTP_CREATED);
    }

    #[Route('/modifyExercise/{id<\d+>}', 'api_modifyExercise', methods: ['POST', 'PUT'])]
    public function modifyExercise(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {
        $excercise = $entityManager->find(Exercises::class, $id);

        if (!$excercise) {
            return $this->json(['error' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        $name = Exercises::validate(strtolower($data['name']));
        $description = Exercises::validate(strtolower($data['description']));
        $category = Exercises::validate($data['category']);

        $name_regex = "/^[a-z0-9]{1,30}$/";
        $category_regex = "/^[a-z0-9]{1,10}$/";

        if (empty($name) || empty($description) || empty($category)) {
            return $this->json(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (Exercises::exerciseExisting($name, $entityManager)) {
            return $this->json(['error' => 'Exercise already exists', Response::HTTP_BAD_REQUEST]);
        }

        if (!preg_match($name_regex, $name)) {
            return $this->json(['error' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($description) > 500 || strlen($description) < 10) {
            return $this->json(['error' => 'Invalid description format'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($category, ['CHEST', 'SHOULDER', 'TRICEPS', 'BACK', 'BICEPS', 'ABDOMINALS', 'FEMORAL', 'QUADRICEPS', 'CALVES']) || !preg_match($category_regex, $category)) {
            return $this->json(['error' => 'Invalid category'], Response::HTTP_BAD_REQUEST);
        }

        $excercise->setName($name);
        $excercise->setDescription($description);
        $excercise->setCategory($category);

        $entityManager->flush();

        return $this->json(['success' => 'Exercise successfully updated'], Response::HTTP_CREATED);
    }
}
