<?php

namespace App\Controller;

use App\Entity\Roles;
use App\Service\GlobalService;
use App\Service\RoleService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/roles')]
class RolesController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private GlobalService $globalService,
        private RoleService $roleService,
    ) {}

    //!CON JS AL DEVOLVER UN JSON CON EL active SE PUEDE FILTAR EN EL FRONT POR active SIN NECESIDAD DE CREAR UN METODO DE seeAllActiveRoles Y QUITARNIOS EL RECARGAR LA PÁGINA PUDIENDIO HACER UN Switches PARA ALTERNAR ENTRE ACTIVOS O TODOS

    //!COMO RolesController SOLO LO VE EL ADMIN NO HAY NECESIDAD DE CREAR UN ENDPOINT PARA CER UN SOLO ROL, AL CARGAR LA PÁGINA NOS TRAEMOS TODOS LOS ROLES, SI QUIERES VER UNO EN ESPECIDICO PARA MODIFICARLO SOLO HAS DE BUSCARLO CON EL ID CON JS EN EL JSON, NOS QUITAMOS TIEMPOS DE CARGA
    #[Route('/seeAllRoles', name: 'api_seeAllRoles', methods: ['GET'])]
    public function seeAllRoles(EntityManagerInterface $entityManager): JsonResponse
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
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_UNAUTHORIZED);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        if ($thisuserRole !== 'ROLE_ADMIN') {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $roles = $entityManager->getRepository(Roles::class)->findAll();

        if (!$roles) {
            return $this->json(['type' => 'warning', 'message' => 'No roles found'], Response::HTTP_OK);
        }

        $rolesData = [];

        foreach ($roles as $data) {
            $rolesData[] = [
                'id' => $data->getRoleId(),
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
        /** @var \App\Entity\Users $thisuser */
        $thisuser = $this->getUser();
        $thisuserRole = $thisuser->getRole()->getName();
        $thisuserId = $thisuser->getUserId();
        $thisuserStatus = $thisuser->getStatus();

        if (!$thisuser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuserStatus !== 'active') {
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_UNAUTHORIZED);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        if ($thisuserRole !== 'ROLE_ADMIN') {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $role = $entityManager->find(Roles::class, $id);

        if (!$role) {
            return $this->json(['type' => 'error', 'message' => 'No role found'], Response::HTTP_BAD_REQUEST);
        }

        $roleData = [];

        $roleData[] = [
            'id' => $role->getRoleId(),
            'name' => $role->getName(),
            "active" => $role->getActive()
        ];

        return $this->json($roleData, Response::HTTP_OK);
    }

    #[Route('/createRole', name: 'api_createRole', methods: ['POST'])]
    public function createRole(EntityManagerInterface $entityManager, Request $request): JsonResponse
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
                return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_UNAUTHORIZED);
                $this->globalService->forceSignOut($entityManager, $thisuserId);
            }

            if ($thisuserRole !== 'ROLE_ADMIN') {
                return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
            }

            $data = json_decode($request->getContent(), true);

            $name = $this->globalService->validate(strtoupper($data['name'] ?? ""));

            $role_regex = "/^ROLE_[A-Z]{4,50}$/";

            if ($name === "") {
                return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
            }

            if (!preg_match($role_regex, $name)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid name format'], Response::HTTP_BAD_REQUEST);
            }

            if ($this->roleService->roleExisting($name, $entityManager)) {
                return $this->json(['type' => 'error', 'message' => 'Role already exists', Response::HTTP_BAD_REQUEST]);
            }

            $newRole = new Roles();

            $newRole->setName($name);
            $newRole->setActive(true);

            $entityManager->persist($newRole);
            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Role successfully created'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while creating the role'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/deleteRole/{id<\d+>}', name: 'api_deleteRole', methods: ['DELETE'])]
    public function deleteRole(EntityManagerInterface $entityManager, int $id): JsonResponse
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
                return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_UNAUTHORIZED);
                $this->globalService->forceSignOut($entityManager, $thisuserId);
            }

            if ($thisuserRole !== 'ROLE_ADMIN') {
                return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
            }

            $role = $this->roleService->roleExisting($id, $entityManager);

            if (!$role) {
                return $this->json(['type' => 'error', 'message' => 'The role does not exist'], Response::HTTP_BAD_REQUEST);
            }

            $role->setActive(false);
            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Role successfully deleted'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while deleting the role'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/activeRole/{id<\d+>}', name: 'api_activeRole', methods: ['PUT'])]
    public function activeRole(EntityManagerInterface $entityManager, int $id): JsonResponse
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
                return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_UNAUTHORIZED);
                $this->globalService->forceSignOut($entityManager, $thisuserId);
            }

            if ($thisuserRole !== 'ROLE_ADMIN') {
                return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
            }

            $role = $this->roleService->roleExisting($id, $entityManager);

            if (!$role) {
                return $this->json(['type' => 'error', 'message' => 'The role does not exist'], Response::HTTP_BAD_REQUEST);
            }

            $role->setActive(true);
            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Role successfully activated'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while activating the role'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/modifyRole/{id<\d+>}', name: 'api_modifyRole', methods: ['GET', 'PUT'])]
    public function modifyRole(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
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
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_UNAUTHORIZED);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        if ($thisuserRole !== 'ROLE_ADMIN') {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $roles = $entityManager->find(Roles::class, $id);

        if (!$roles) {
            return $this->json(['type' => 'warning', 'message' => 'No role found'], Response::HTTP_BAD_REQUEST);
        }

        if ($request->isMethod('GET')) {
            $rolesData = [
                'id' => $roles->getRoleId(),
                'name' => $roles->getName(),
                "active" => $roles->getActive()
            ];

            return $this->json($rolesData, Response::HTTP_OK);
        }

        if ($request->isMethod('PUT')) {
            try {
                $data = json_decode($request->getContent(), true);

                $name = $this->globalService->validate(strtoupper($data['name'] ?? ""));
                $active = array_key_exists('active', $data)
                    ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null;

                $role_regex = "/^ROLE_[A-Z]{4,50}$/";

                if ($name === "" || $active === null) {
                    return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
                }

                if ($roles->getName() === "ROLE_ADMIN") {
                    return $this->json(['type' => 'error', 'message' => 'The administrator role cannot be changed'], Response::HTTP_BAD_REQUEST);
                }

                if ($this->roleService->roleExisting2($id, $name, $entityManager)) {
                    return $this->json(['type' => 'error', 'message' => 'Role already exists', Response::HTTP_BAD_REQUEST]);
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
            } catch (\Exception $e) {
                return $this->json(['type' => 'error', 'message' => 'An error occurred while modifying the role'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return $this->json(['type' => 'error', 'message' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
