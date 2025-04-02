<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UsersController extends AbstractController
{
    #[Route('/seeAllUsers', name: 'api_seeAllUsers', methods: ['GET'])]
    public function seeAllUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(Users::class)->findAll();

        if (!$users) {
            return $this->json(['type' => 'warning', 'message' => 'No users found'], Response::HTTP_OK);
        }

        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id_usr' => $user->getIdUsr(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'password' => $user->getPassword(),
                'role' => $user->getRole(),
                'active' => $user->getActive()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/seeOneUser/{id<\d+>}', name: 'api_seeOneUser', methods: ['GET'])]
    public function seeOneUser(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $user = $entityManager->find(Users::class, $id);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data[] = [
            'id_usr' => $user->getIdUsr(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'password' => $user->getPassword(),
            'role' => $user->getRole(),
            'active' => $user->getActive()
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/singUp', name: 'api_singUp', methods: ['POST'])]
    public function singUp(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = Users::validate(strtolower($data['email']));
        $username = Users::validate(strtolower($data['username']));
        $password = $data['password'];
        $repeatPassword = $data['repeatPassword'];

        $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
        $username_regex = "/^[a-z0-9]{5,20}$/";

        if (empty($email) || empty($username) || empty($password) || empty($repeatPassword)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            return $this->json(['type' => 'error', 'message' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match($password_regex, $password) || !preg_match($password_regex, $repeatPassword)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid password format'], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match($username_regex, $username)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid username format'], Response::HTTP_BAD_REQUEST);
        }

        if (Users::userExisting($email, $username, $entityManager)) {
            return $this->json(['type' => 'error', 'message' => 'User already exists', Response::HTTP_BAD_REQUEST]);
        }

        if ($password !== $repeatPassword) {
            return $this->json(['type' => 'error', 'message' => 'Passwords dont match'], Response::HTTP_BAD_REQUEST);
        }

        $newUser = new Users();

        $newUser->setEmail($email);
        $newUser->setUsername($username);
        $newUser->setPassword(Users::hashPassword($password));
        $newUser->setRole('USER');
        $newUser->setActive(true);

        $entityManager->persist($newUser);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'User successfully created'], Response::HTTP_CREATED);
    }

    #[Route('/singIn', name: 'api_singIn', methods: ['POST'])]
    public function singIn(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $emailUsername = Users::validate(strtolower($data['emailUsername']));
        $password = Users::validate($data['password']);

        $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
        $username_regex = "/^[a-z0-9]{5,20}$/";

        if (empty($emailUsername) || empty($password)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (str_contains($emailUsername, '@')) {
            if (!filter_var($emailUsername, FILTER_VALIDATE_EMAIL) || strlen($emailUsername) > 255) {
                return $this->json(['type' => 'error', 'message' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            if (!preg_match($username_regex, $emailUsername)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid username format'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!preg_match($password_regex, $password)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid password format'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::passwordsMatch($emailUsername, $password, $entityManager)) {
            return $this->json(['type' => 'error', 'message' => 'User or password doesnt match'], Response::HTTP_BAD_REQUEST);
        }

        $id_user = Users::getIdUser($emailUsername, $entityManager);

        if (!$id_user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        session_start();
        $_SESSION['id_user'] = $id_user;

        return $this->json(['type' => 'success', 'message' => 'Session successfully started'], Response::HTTP_OK);
    }

    #[Route('/signOut', name: 'api_signOut', methods: ['POST'])]
    public function signOut(): JsonResponse
    {
        session_start();

        unset($_SESSION['id_user']);

        return $this->json(['type' => 'success', 'message' => 'Session successfully ended'], Response::HTTP_OK);
    }

    #[Route('/deleteUser/{id<\d+>}', name: 'api_deleteUser', methods: ['DELETE', 'POST'])]
    public function deleteUser(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $delUser = $entityManager->find(Users::class, $id);

        if (!$delUser) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $delUser->setActive(false);
        $entityManager->flush();


        return $this->json(['type' => 'success', 'message' => 'User successfully deleted'], Response::HTTP_CREATED);
    }

    #[Route('/modifyUser/{id<\d+>}', name: 'api_modifyUser', methods: ['PUT', 'POST'])]
    public function modifyUser(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
    {
        $user = $entityManager->find(Users::class, $id);

        //!VER TEMA COMPROBAR QUE EL ID QUE SE PASA POR LA URL SEA EL MISMO QUE EL DEL USUARIO QUE LO SOLICITA SOLO PARA LOS USUARIOS NO SE PUEDE HACER CON EL ADMIN YA QUE EL PUEDE MODIFICAR LOS PERFILES DE LOS USUARIOS POR EJEMPLO PARA MODIFICAR EL ROLE

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        $username = Users::validate(strtolower($data['username']));
        $password = Users::validate($data['password']);
        $role = Users::validate($data['role']);

        $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
        $username_regex = "/^[a-z0-9]{5,20}$/";
        $role_regex = "/^[A-Z0-9]{1,5}$/";

        if (empty($username)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match($username_regex, $username)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid username format'], Response::HTTP_BAD_REQUEST);
        }

        if (Users::userExisting2($id, $username, $entityManager)) {
            return $this->json(['type' => 'error', 'message' => 'User already exists', Response::HTTP_BAD_REQUEST]);
        }

        $user->setUsername($username);

        if (!empty($password)) {
            if (!preg_match($password_regex, $password)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid password format'], Response::HTTP_BAD_REQUEST);
            }

            $hashedPassword = Users::hashPassword($password);
            $user->setPassword($hashedPassword);
        }

        if (!empty($role)) {
            if (!in_array($role, ['ADMIN', 'USER', 'COACH']) || !preg_match($role_regex, $role)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
            }
            $user->setRole($role);
        }

        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'User successfully updated'], Response::HTTP_CREATED);
    }
}
