<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Entity\Exercises;
use App\Entity\Users;
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
    private function forceSignOut(EntityManagerInterface $entityManager, int $id_user)
    {
        Users::removeToken($entityManager, $id_user);

        setcookie("token", "", time() - 3600, "/");

        unset($_SESSION['id_user']);
    }

    //!CON JS AL DEVOLVER UN JSON CON EL active SE PUEDE FILTAR EN EL FRONT POR active SIN NECESIDAD DE CREAR UN METODO DE seeAllActiveExercises Y QUITARNIOS EL RECARGAR LA PÁGINA PUDIENDIO HACER UN Switches PARA ALTERNAR ENTRE ACTIVOS O TODOS
    #[Route('/seeAllExercises', name: 'api_seeAllExercises', methods: ['GET'])]
    public function seeAllExercises(EntityManagerInterface $entityManager): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class, $_SESSION['id_user']);
        $role = $thisUser->getRole()->getName();

        if ($role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $exercises = $entityManager->getRepository(Exercises::class)->findAll();

        if (!$exercises) {
            return $this->json(['type' => 'warning', 'message' => 'No exercises found'], Response::HTTP_OK);
        }

        $data = [];

        foreach ($exercises as $excercise) {
            $data[] = [
                'id_exe' => $excercise->getIdExe(),
                'name' => $excercise->getName(),
                'description' => $excercise->getDescription(),
                'category' => $excercise->getCategory()->getName(),
                'likes' => $excercise->getLikes(),
                'active' => $excercise->getActive()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    //!SE CREA POS SI SE QUIERE CONSUMIR COMO API, NO SE USA EN EL FRONT
    #[Route('/seeAllActiveExercises', name: 'api_seeAllActiveExercises', methods: ['GET'])]
    public function seeAllActiveExercises(EntityManagerInterface $entityManager): JsonResponse
    {
        $exercises = $entityManager->createQuery(
            'SELECT e FROM App\Entity\Exercises e WHERE e.active = true'
        )->getResult();

        if (!$exercises) {
            return $this->json(['type' => 'warning', 'message' => 'No exercises found'], Response::HTTP_OK);
        }

        $data = [];

        foreach ($exercises as $exercise) {
            $data[] = [
                'id_exe' => $exercise->getIdExe(),
                'name' => $exercise->getName(),
                'description' => $exercise->getDescription(),
                'category' => $exercise->getCategory(),
                'likes' => $exercise->getLikes(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    //!NO HAY NECESIDAD DE CREAR UN ENDPOINT PARA VER UN SOLO EJERCICIO, AL CARGAR LA PÁGINA NOS TRAEMOS TODOS LOS EJERCICIOS, SI QUIERES VER UNO EN ESPECIDICO PARA MODIFICARLO SOLO HAS DE BUSCARLO CON EL ID CON JS EN EL JSON, NOS QUITAMOS TIEMPOS DE CARGA
    #[Route('/seeOneExcercise/{id<\d+>}', name: 'api_seeOneExcercise', methods: ['GET'])]
    public function seeOneExcercise(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();
        $id_user = $_SESSION['id_user'];

        $thisUser = $entityManager->find(Users::class, $id_user);

        $role = $thisUser->getRole()->getName();

        if ($role !== 'ROLE_ADMIN' && !Exercises::isActive($id, $entityManager)) {
            return $this->json(['type' => 'error', 'message' => 'The excercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $excercise = $entityManager->find(Exercises::class, $id);

        if (!$excercise) {
            return $this->json(['type' => 'error', 'message' => 'The excercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data[] = [
            'id_exe' => $excercise->getIdExe(),
            'name' => $excercise->getName(),
            'description' => $excercise->getDescription(),
            'category' => $excercise->getCategory()->getName(),
            'likes' => $excercise->getLikes(),
            'active' => $excercise->getActive()
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/addExercise', name: 'api_addExercise', methods: ['POST'])]
    public function addExercise(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class, $_SESSION['id_user']);
        $role = $thisUser->getRole()->getName();

        if ($role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        $name = Exercises::validate(trim(strtolower($data['name'] ?? "")));
        $description = Exercises::validate(trim(strtolower($data['description'] ?? "")));
        $category_id = (int)Exercises::validate($data['category'] ?? "");

        $name_regex = "/^[a-z0-9]{1,30}$/";
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

        if (Exercises::exerciseExisting($name, $entityManager)) {
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
        $exercise->setLikes(0);
        $exercise->setActive(true);

        $entityManager->persist($exercise);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Exercise successfully created'], Response::HTTP_CREATED);
    }

    //!DEPENDIENDO DE LO QUE SE DIGA SE ÙEDE QUITAR PQ YA LO HACE modifyExercise
    #[Route('/deleteExercise/{id<\d+>}', name: 'api_deleteExercise', methods: ['DELETE', 'PUT', 'POST'])]
    public function deleteExercise(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class, $_SESSION['id_user']);
        $role = $thisUser->getRole()->getName();

        if ($role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $delexercise = $entityManager->find(Exercises::class, $id);

        if (!$delexercise) {
            return $this->json(['type' => 'error', 'message' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $delexercise->setActive(false);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Exercise successfully deleted'], Response::HTTP_CREATED);
    }

    //!DEPENDIENDO DE LO QUE SE DIGA SE ÙEDE QUITAR PQ YA LO HACE modifyExercise
    #[Route('/activeExercise/{id<\d+>}', name: 'app_activeExercise', methods: ['PUT', 'POST'])]
    public function activeExercise(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class, $_SESSION['id_user']);
        $role = $thisUser->getRole()->getName();

        if ($role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $exercise = $entityManager->find(Exercises::class, $id);

        if (!$exercise) {
            return $this->json(['type' => 'error', 'message' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $exercise->setActive(true);

        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Exercise successfully activated'], Response::HTTP_CREATED);
    }

    #[Route('/modifyExercise/{id<\d+>}', name: 'api_modifyExercise', methods: ['PUT', 'POST',  'GET'])]
    public function modifyExercise(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class, $_SESSION['id_user']);
        $role = $thisUser->getRole()->getName();

        if ($role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $excercise = $entityManager->find(Exercises::class, $id);

        if (!$excercise) {
            return $this->json(['type' => 'error', 'message' => 'The exercise does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $categories = $entityManager->getRepository(Categories::class)->findAll();

        $categoriesData = [];

        foreach ($categories as $data) {
            $categoriesData[] = [
                'id' => $data->getIdCat(),
                'name' => $data->getName(),
            ];
        }

        if ($request->isMethod('GET')) {
            $data = [
                'id_exe' => $excercise->getIdExe(),
                'name' => $excercise->getName(),
                'description' => $excercise->getDescription(),
                'category_id' => $excercise->getCategory()->getName(),
                'category_id' => $excercise->getCategory()->getIdCat(),
                'categories' => $categoriesData,
                'likes' => $excercise->getLikes(),
                'active' => $excercise->getActive()
            ];

            return $this->json($data, Response::HTTP_OK);
        }

        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $data = json_decode($request->getContent(), true);

            $name = Exercises::validate(trim(strtolower($data['name'] ?? "")));
            $description = Exercises::validate(trim(strtolower($data['description'] ?? "")));
            $category_id = (int)Exercises::validate($data['category'] ?? "");
            $active = array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null;

            $name_regex = "/^[a-z0-9]{1,30}$/";
            $description_regex = "/^[a-zA-Z0-9]{10,500}$/";

            if ($name === "" || $description === "" || $category_id === "" || $active === null) {
                return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
            }

            if (Exercises::exerciseExisting2($id, $name, $entityManager)) {
                return $this->json(['type' => 'error', 'message' => 'Exercise already exists', Response::HTTP_BAD_REQUEST]);
            }

            if (!preg_match($name_regex, $name)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
            }

            if (preg_match($description_regex, $description)) {
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
