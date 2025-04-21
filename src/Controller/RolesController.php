<?php

namespace App\Controller;

use App\Entity\Roles;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/roles')]
class RolesController extends AbstractController
{
    private function forceSignOut(EntityManagerInterface $entityManager, int $id_user)
    {
        Users::removeToken($entityManager, $id_user);

        setcookie("token", "", time() - 3600, "/");

        unset($_SESSION['id_user']);
    }
    //!CON JS AL DEVOLVER UN JSON CON EL active SE PUEDE FILTAR EN EL FRONT POR active SIN NECESIDAD DE CREAR UN METODO DE seeAllActiveRoles Y QUITARNIOS EL RECARGAR LA PÁGINA PUDIENDIO HACER UN Switches PARA ALTERNAR ENTRE ACTIVOS O TODOS

    //!COMO RolesController SOLO LO VE EL ADMIN NO HAY NECESIDAD DE CREAR UN ENDPOINT PARA CER UN SOLO ROL, AL CARGAR LA PÁGINA NOS TRAEMOS TODOS LOS ROLES, SI QUIERES VER UNO EN ESPECIDICO PARA MODIFICARLO SOLO HAS DE BUSCARLO CON EL ID CON JS EN EL JSON, NOS QUITAMOS TIEMPOS DE CARGA
    #[Route('/seeAllRoles', name: 'api_seeAllRoles', methods: ['GET'])]
    public function seeAllRoles(EntityManagerInterface $entityManager): JsonResponse
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

        $roles = $entityManager->getRepository(Roles::class)->findAll();

        if (!$roles) {
            return $this->json(['type' => 'warning', 'message' => 'No roles found'], Response::HTTP_OK);
        }

        $rolesData = [];

        foreach ($roles as $data) {
            $rolesData[] = [
                'id' => $data->getIdRole(),
                'name' => $data->getName(),
                "active" => $data->getActive()
            ];
        }

        return $this->json($rolesData, Response::HTTP_OK);
    }

    //!SE CREA POS SI SE QUIERE CONSUMIR COMO API, NO SE USA EN EL FRONT
    #[Route('/seeOneRole/{id<\d+>}', name: 'seeOneRole', methods: ['GET'])]
    public function seeOneRole(EntityManagerInterface $entityManager, int $id): JsonResponse
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

        $roles = $entityManager->find(Roles::class, $id);

        if (!$roles) {
            return $this->json(['type' => 'error', 'message' => 'No role found'], Response::HTTP_OK);
        }

        $rolesData = [];

        $rolesData[] = [
            'id' => $roles->getIdRole(),
            'name' => $roles->getName(),
            "active" => $roles->getActive()
        ];

        return $this->json($rolesData, Response::HTTP_OK);
    }

    #[Route('/createRole', name: 'api_createRole', methods: ['POST'])]
    public function createRole(EntityManagerInterface $entityManager, Request $request): JsonResponse
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

        $name = Roles::validate(strtoupper($data['name'] ?? ""));

        $role_regex = "/^ROLE_[A-Z]{4,50}$/";

        if ($name === "") {
            return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match($role_regex, $name)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
        }

        if (Roles::roleExisting($name, $entityManager)) {
            return $this->json(['type' => 'error', 'message' => 'Role already exists', Response::HTTP_BAD_REQUEST]);
        }

        $newRole = new Roles();

        $newRole->setName($name);
        $newRole->setActive(true);

        $entityManager->persist($newRole);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Role successfully created'], Response::HTTP_CREATED);
    }

    #[Route('/deleteRole/{id<\d+>}', name: 'api_deleteRole', methods: ['DELETE', 'PUT', 'POST'])]
    public function deleteRole(EntityManagerInterface $entityManager, int $id): JsonResponse
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

        $role = $entityManager->find(Roles::class, $id);

        if (!$role) {
            return $this->json(['type' => 'error', 'message' => 'The role does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $role->setActive(false);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Role successfully deleted'], Response::HTTP_CREATED);
    }

    #[Route('/activeRole/{id<\d+>}', name: 'api_activeRole', methods: ['PUT', 'POST'])]
    public function activeRole(EntityManagerInterface $entityManager, int $id): JsonResponse
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

        $role = $entityManager->find(Roles::class, $id);

        if (!$role) {
            return $this->json(['type' => 'error', 'message' => 'The role does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $role->setActive(true);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'Role successfully activated'], Response::HTTP_CREATED);
    }

    #[Route('/modifyRole/{id<\d+>}', name: 'api_modifyRole', methods: ['GET', 'PUT', 'POST'])]
    public function modifyRole(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
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

        $roles = $entityManager->find(Roles::class, $id);

        if (!$roles) {
            return $this->json(['type' => 'warning', 'message' => 'No role found'], Response::HTTP_OK);
        }

        if ($request->isMethod('GET')) {
            $rolesData = [
                'id' => $roles->getIdRole(),
                'name' => $roles->getName(),
                "active" => $roles->getActive()
            ];

            return $this->json($rolesData, Response::HTTP_OK);
        }

        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $data = json_decode($request->getContent(), true);

            $name = Roles::validate(strtoupper($data['name'] ?? ""));
            $active = array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null;

            $role_regex = "/^ROLE_[A-Z]{4,50}$/";

            if ($name === "" || $active === null) {
                return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
            }

            if (Roles::roleExisting2($id, $name, $entityManager)) {
                return $this->json(['type' => 'error', 'message' => 'Exercise already exists', Response::HTTP_BAD_REQUEST]);
            }

            if (!preg_match($role_regex, $name)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
            }

            $roles->setName($name);
            if ($active !== null) {
                $roles->setActive($active);
            }

            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Exercise successfully updated'], Response::HTTP_CREATED);
        }

        return $this->json(['type' => 'error', 'message' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
