<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/categories')]
class CategoriesController extends AbstractController
{
    private function forceSignOut(EntityManagerInterface $entityManager, int $id_user)
    {
        Users::removeToken($entityManager, $id_user);

        setcookie("token", "", time() - 3600, "/");

        unset($_SESSION['id_user']);
    }

    #[Route('/seeAllCategories', name: 'api_seeAllCategories', methods: ['GET'])]
    public function seeAllCategories(EntityManagerInterface $entityManager): JsonResponse
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
        $this_role = $thisUser->getRole()->getName();

        if ($this_role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $categories = $entityManager->getRepository(Categories::class)->findAll();

        if (!$categories) {
            return $this->json(['type' => 'warning', 'message' => 'No categories found'], Response::HTTP_OK);
        }

        $categoriesData = [];

        foreach ($categories as $data) {
            $categoriesData[] = [
                'id' => $data->getIdCat(),
                'name' => $data->getName(),
                'active' => $data->getActive()
            ];
        }

        return $this->json($categoriesData, Response::HTTP_OK);
    }

    //!SE CREA POS SI SE QUIERE CONSUMIR COMO API, NO SE USA EN EL FRONT
    #[Route('/seeOneCategory/{id<\d+>}', name: 'seeOneCategory', methods: ['GET'])]
    public function seeOneCategory(EntityManagerInterface $entityManager, int $id): JsonResponse
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
        $this_role = $thisUser->getRole()->getName();

        if ($this_role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $category = $entityManager->find(Categories::class, $id);

        if (!$category) {
            return $this->json(['type' => 'error', 'message' => 'Category not found'], Response::HTTP_OK);
        }

        $categoryData = [];

        $categoryData[] = [
            'id' => $category->getIdCat(),
            'name' => $category->getName(),
            "active" => $category->getActive()
        ];

        return $this->json($categoryData, Response::HTTP_OK);
    }

    #[Route('/createCategory', name: 'api_createCategory', methods: ['GET'])]
    public function createCategory(EntityManagerInterface $entityManager, Request $request): JsonResponse
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
        $this_role = $thisUser->getRole()->getName();

        if ($this_role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        $name = Categories::validate(strtoupper($data['name'] ?? ""));

        $categorie_regex = "/^[A-Z]{3,50}$/";

        if ($name === "") {
            return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match($categorie_regex, $name)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
        }

        if (Categories::categoryExisting($name, $entityManager)) {
            return $this->json(['type' => 'error', 'message' => 'Category already exists', Response::HTTP_BAD_REQUEST]);
        }

        $newCategory = new Categories();

        $newCategory->setName($name);
        $newCategory->setActive(true);

        $entityManager->persist($newCategory);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Categorie successfully created'], Response::HTTP_CREATED);
    }

    #[Route('/deleteCategory/{id<\d+>}', name: 'api_deleteCategory', methods: ['DELETE', 'PUT', 'POST'])]
    public function deleteCategory(EntityManagerInterface $entityManager, int $id): JsonResponse
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
        $this_role = $thisUser->getRole()->getName();

        if ($this_role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $categorie = $entityManager->find(Categories::class, $id);

        if (!$categorie) {
            return $this->json(['type' => 'error', 'message' => 'The categorie does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $categorie->setActive(false);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Categorie successfully deleted'], Response::HTTP_CREATED);
    }

    #[Route('/activeCategory/{id<\d+>}', name: 'api_activeCategory', methods: ['DELETE', 'PUT', 'POST'])]
    public function activeCategory(EntityManagerInterface $entityManager, int $id): JsonResponse
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
        $this_role = $thisUser->getRole()->getName();

        if ($this_role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $categorie = $entityManager->find(Categories::class, $id);

        if (!$categorie) {
            return $this->json(['type' => 'error', 'message' => 'The categorie does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $categorie->setActive(true);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Categorie successfully activated'], Response::HTTP_CREATED);
    }

    #[Route('/modifyCategory/{id<\d+>}', name: 'api_modifyCategory', methods: ['GET', 'PUT', 'POST'])]
    public function modifyCategory(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
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
        $this_role = $thisUser->getRole()->getName();

        if ($this_role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $category = $entityManager->find(Categories::class, $id);

        if (!$category) {
            return $this->json(['type' => 'warning', 'message' => 'No category found'], Response::HTTP_OK);
        }

        if ($request->isMethod('GET')) {
            $categoryData = [
                'id' => $category->getIdCat(),
                'name' => $category->getName(),
                "active" => $category->getActive()
            ];

            return $this->json($categoryData, Response::HTTP_OK);
        }

        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $data = json_decode($request->getContent(), true);

            $name = Categories::validate(strtoupper($data['name'] ?? ""));
            $active = array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null;

            $categorie_regex = "/^[A-Z]{3,50}$/";

            if ($name === "" || $active === null) {
                return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
            }

            if (Categories::categoryExisting2($id, $name, $entityManager)) {
                return $this->json(['type' => 'error', 'message' => 'Category already exists', Response::HTTP_BAD_REQUEST]);
            }

            if (!preg_match($categorie_regex, $name)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
            }

            $category->setName($name);
            if ($active !== null) {
                $category->setActive($active);
            }

            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Category successfully updated'], Response::HTTP_CREATED);
        }

        return $this->json(['type' => 'error', 'message' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
