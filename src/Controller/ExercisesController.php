<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Entity\Exercises;
use App\Entity\Users;
use App\Service\CategoryService;
use App\Service\ExerciseService;
use App\Service\GlobalService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

//!VER PARA QUE SOLO SEA ACCESIBLE PARA LOS ADMINS Y ENTRENADORES (EXCEPCIONES COMO VER LOS EJERCICIOS)
#[Route('/api/exercises')]
class ExercisesController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private GlobalService $globalService,
        private ExerciseService $exerciseService,
        private CategoryService $categoryService,
    ) {}

    #[Route('/seeAllExercises', name: 'api_seeAllExercises', methods: ['GET'])]
    public function seeAllExercises(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\Users $thisuser */
        $thisuser = $this->getUser();
        $thisuserRole = $thisuser->getRole()->getName();
        $thisuserId = $thisuser->getUserId();
        $thisuserStatus = $thisuser->getStatus();

        if (!$thisuser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuserStatus !== 'active') {
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        if (!in_array($thisuserRole, ["ROLE_ADMIN", "ROLE_COACH"])) {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator or a coach'], Response::HTTP_BAD_REQUEST);
        }

        $exercises = $entityManager->getRepository(Exercises::class)->findAll();

        if (!$exercises) {
            return $this->json(['type' => 'warning', 'message' => 'No exercises found'], Response::HTTP_BAD_REQUEST);
        }

        $data = [];

        foreach ($exercises as $excercise) {
            $likes = $excercise->getExerciseLikes()?->getLikes() ?? 0;
            $data[] = [
                'id_exe' => $excercise->getExerciseId(),
                'name' => $excercise->getName(),
                'description' => $excercise->getDescription(),
                'category' => $excercise->getCategory()->getName(),
                'likes' => $likes,
                'active' => $excercise->getActive(),
                'creator' => $excercise->getUser()->getDisplayUsername(),
                'creator_id' => $excercise->getUser()->getUserId(),
                'created_at' => $excercise->getCreatedAt()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/seeAllActiveExercises', name: 'api_seeAllActiveExercises', methods: ['GET'])]
    public function seeAllActiveExercises(EntityManagerInterface $entityManager): JsonResponse
    {
        $exercises = $entityManager->createQuery(
            'SELECT e FROM App\Entity\Exercises e WHERE e.active = true'
        )->getResult();

        if (!$exercises) {
            return $this->json(['type' => 'warning', 'message' => 'No exercises found'], Response::HTTP_BAD_REQUEST);
        }

        $data = [];

        foreach ($exercises as $excercise) {
            $likes = $excercise->getExerciseLikes()?->getLikes() ?? 0;
            $data[] = [
                'id_exe' => $excercise->getExerciseId(),
                'name' => $excercise->getName(),
                'description' => $excercise->getDescription(),
                'category' => $excercise->getCategory()->getName(),
                'likes' => $likes,
                'active' => $excercise->getActive(),
                'creator' => $excercise->getUser()->getDisplayUsername(),
                'creator_id' => $excercise->getUser()->getUserId(),
                'created_at' => $excercise->getCreatedAt()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    //!NO HAY NECESIDAD DE CREAR UN ENDPOINT PARA VER UN SOLO EJERCICIO, AL CARGAR LA PÁGINA NOS TRAEMOS TODOS LOS EJERCICIOS, SI QUIERES VER UNO EN ESPECIDICO PARA MODIFICARLO SOLO HAS DE BUSCARLO CON EL ID CON JS EN EL JSON, NOS QUITAMOS TIEMPOS DE CARGA
    #[Route('/seeOneExercise/{id<\d+>}', name: 'api_seeOneExercise', methods: ['GET'])]
    public function seeOneExcercise(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $excercise = $entityManager->find(Exercises::class, $id);

        if (!$excercise) {
            return $this->json(['type' => 'error', 'message' => 'The excercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        if ($excercise->getActive() === false) {
            return $this->json(['type' => 'error', 'message' => 'The excercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $likes = $excercise->getExerciseLikes()?->getLikes() ?? 0;

        $data[] = [
            'id_exe' => $excercise->getExerciseId(),
            'name' => $excercise->getName(),
            'description' => $excercise->getDescription(),
            'category' => $excercise->getCategory()->getName(),
            'likes' => $likes,
            'creator' => $excercise->getUser()->getDisplayUsername(),
            'creator_id' => $excercise->getUser()->getUserId(),
            'created_at' => $excercise->getCreatedAt()
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/addExercise', name: 'api_addExercise', methods: ['POST'])]
    public function addExercise(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        /** @var \App\Entity\Users $thisuser */
        $thisuser = $this->getUser();
        $thisuserRole = $thisuser->getRole()->getName();
        $thisuserId = $thisuser->getUserId();
        $thisuserStatus = $thisuser->getStatus();

        if (!$thisuser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuserStatus !== 'active') {
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        if (!in_array($thisuserRole, ["ROLE_ADMIN", "ROLE_COACH"])) {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator or a coach'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        $name = $this->globalService->validate(trim(strtolower($data['name'] ?? "")));
        $description = $this->globalService->validate(trim(strtolower($data['description'] ?? "")));
        $category_id = (int)$this->globalService->validate($data['category_id'] ?? "");

        $name_regex = "/^[a-z]{1,30}$/";
        $description_regex = "/^[a-zA-Z0-9]{10,500}$/";

        if ($name === "" || $description === "" || $category_id === "") {
            return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match($name_regex, $name)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
        }

        if (preg_match($description_regex, $description)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid description format'], Response::HTTP_BAD_REQUEST);
        }

        if ($entityManager->getRepository(Exercises::class)->findOneBy(['name' => $name])) {
            return $this->json(['type' => 'error', 'message' => 'Exercise already exists'], Response::HTTP_BAD_REQUEST);
        }

        $category = $entityManager->find(Categories::class, $category_id);

        if (!$category) {
            return $this->json(['type' => 'error', 'message' => 'Invalid category'], Response::HTTP_BAD_REQUEST);
        }

        $exercise = new Exercises();
        $exercise->setName($name);
        $exercise->setDescription($description);
        $exercise->setCategory($category);
        $exercise->setActive(true);
        $exercise->setUser($thisuser);
        $exercise->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($exercise);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Exercise successfully created'], Response::HTTP_CREATED);
    }

    //!DEPENDIENDO DE LO QUE SE DIGA SE ÙEDE QUITAR PQ YA LO HACE modifyExercise
    #[Route('/deleteExercise/{id<\d+>}', name: 'api_deleteExercise', methods: ['DELETE'])]
    public function deleteExercise(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        try {
            /** @var \App\Entity\Users $thisuser */
            $thisuser = $this->getUser();
            $thisuserRole = $thisuser->getRole()->getName();
            $thisuserId = $thisuser->getUserId();
            $thisuserStatus = $thisuser->getStatus();

            if (!$thisuser) {
                return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
            }

            if ($thisuserStatus !== 'active') {
                return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
                $this->globalService->forceSignOut($entityManager, $thisuserId);
            }

            if (!in_array($thisuserRole, ["ROLE_ADMIN", "ROLE_COACH"])) {
                return $this->json(['type' => 'error', 'message' => 'You are not an administrator or a coach'], Response::HTTP_BAD_REQUEST);
            }

            $delexercise = $entityManager->find(Exercises::class, $id);

            if (!$delexercise) {
                return $this->json(['type' => 'error', 'message' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
            }

            $delexercise->setActive(false);
            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Exercise successfully deleted'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while deleting the categoey'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //!DEPENDIENDO DE LO QUE SE DIGA SE ÙEDE QUITAR PQ YA LO HACE modifyExercise
    #[Route('/activeExercise/{id<\d+>}', name: 'app_activeExercise', methods: ['PUT'])]
    public function activeExercise(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        try {
            /** @var \App\Entity\Users $thisuser */
            $thisuser = $this->getUser();
            $thisuserRole = $thisuser->getRole()->getName();
            $thisuserId = $thisuser->getUserId();
            $thisuserStatus = $thisuser->getStatus();

            if (!$thisuser) {
                return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
            }

            if ($thisuserStatus !== 'active') {
                return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
                $this->globalService->forceSignOut($entityManager, $thisuserId);
            }

            if (!in_array($thisuserRole, ["ROLE_ADMIN", "ROLE_COACH"])) {
                return $this->json(['type' => 'error', 'message' => 'You are not an administrator or a coach'], Response::HTTP_BAD_REQUEST);
            }

            $exercise = $entityManager->find(Exercises::class, $id);

            if (!$exercise) {
                return $this->json(['type' => 'error', 'message' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
            }

            $exercise->setActive(true);
            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Exercise successfully activated'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while deleting the categoey'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/modifyExercise/{id<\d+>}', name: 'api_modifyExercise', methods: ['PUT', 'GET'])]
    public function modifyExercise(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
    {
        /** @var \App\Entity\Users $thisuser */
        $thisuser = $this->getUser();
        $thisuserRole = $thisuser->getRole()->getName();
        $thisuserId = $thisuser->getUserId();
        $thisuserStatus = $thisuser->getStatus();

        if (!$thisuser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuserStatus !== 'active') {
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        if (!in_array($thisuserRole, ["ROLE_ADMIN", "ROLE_COACH"])) {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator or a coach'], Response::HTTP_BAD_REQUEST);
        }

        $excercise = $entityManager->find(Exercises::class, $id);

        if (!$excercise) {
            return $this->json(['type' => 'error', 'message' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $categories = $entityManager->getRepository(Categories::class)->findAll();

        $categoriesData = [];

        foreach ($categories as $data) {
            $categoriesData[] = [
                'id' => $data->getCategoryId(),
                'name' => $data->getName(),
            ];
        }

        if ($request->isMethod('GET')) {
            $data = [
                'id_exe' => $excercise->getExerciseId(),
                'name' => $excercise->getName(),
                'description' => $excercise->getDescription(),
                'category_id' => $excercise->getCategory()->getName(),
                'category_id' => $excercise->getCategory()->getCategoryId(),
                'categories' => $categoriesData,
                'active' => $excercise->getActive()
            ];

            return $this->json($data, Response::HTTP_OK);
        }

        if ($request->isMethod('PUT')) {
            $data = json_decode($request->getContent(), true);

            $name = $this->globalService->validate(trim(strtolower($data['name'] ?? "")));
            $description = $this->globalService->validate(trim(strtolower($data['description'] ?? "")));
            $category_id = (int)$this->globalService->validate($data['category_id'] ?? "");
            $active = array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null;

            $name_regex = "/^[a-z0-9]{1,30}$/";
            $description_regex = "/^[a-zA-Z0-9]{10,500}$/";

            if ($name === "" || $description === "" || $category_id === "" || $active === null) {
                return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
            }

            if ($this->exerciseService->exerciseExisting2($id, $name, $entityManager)) {
                return $this->json(['type' => 'error', 'message' => 'Exercise already exists'], Response::HTTP_BAD_REQUEST);
            }

            if (!preg_match($name_regex, $name)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
            }

            if (!preg_match($description_regex, $description)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid description format'], Response::HTTP_BAD_REQUEST);
            }

            $category = $entityManager->find(Categories::class, $category_id);

            if (!$category) {
                return $this->json(['type' => 'error', 'message' => 'Invalid category'], Response::HTTP_BAD_REQUEST);
            }
            $excercise->setName($name);
            $excercise->setDescription($description);
            $excercise->setCategory($category);
            if ($active !== null) {
                $excercise->setActive($active);
            }

            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Exercise successfully updated'], Response::HTTP_CREATED);
        }

        return $this->json(['type' => 'error', 'message' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
