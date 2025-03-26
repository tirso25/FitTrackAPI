<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class UsersController extends AbstractController
{
    #[Route('/seeAllUsers', 'api_seeAllUsers', methods: ['GET'])]
    public function seeAllUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        $users = $entityManager->getRepository(Users::class)->findAll();

        if (!$users) {
            return $this->json(['alert' => 'No users found'], Response::HTTP_OK);
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

    #[Route('/seeOneUser/{id<\d+>}', 'api_seeOneUser', methods: ['GET'])]
    public function seeOneUser(EntityManagerInterface $entityManager, $id): JsonResponse
    {
        $user = $entityManager->find(Users::class, $id);

        if (!$user) {
            return $this->json(['error' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data[] = [
            'id_usr' => $user->getIdUsr(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'password' => $user->getPassword(),
            'role' => $user->getRole()
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/singUp', 'api_singUp', methods: ['POST'])]
    public function singUp(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = Users::validate($data['email']);
        $username = Users::validate($data['username']);
        $password = $data['password'];
        $repeatPassword = $data['repeatPassword'];


        if (empty($email) || empty($username) || empty($password) || empty($repeatPassword)) {
            return $this->json(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        if (Users::userExisting($email, $username, $entityManager)) {
            return $this->json(['error' => 'User already exists', Response::HTTP_BAD_REQUEST]);
        }

        if ($password !== $repeatPassword) {
            return $this->json(['error' => 'Passwords dont match'], Response::HTTP_BAD_REQUEST);
        }

        $newUser = new Users();

        $newUser->setEmail($email);
        $newUser->setUsername($username);
        $newUser->setPassword(Users::hashPassword($password));
        $newUser->setRole('USER');
        $newUser->setActive(true);

        $entityManager->persist($newUser);
        $entityManager->flush();

        return $this->json(['success' => 'User successfully created'], Response::HTTP_CREATED);
    }

    #[Route('/singIn', 'api_singIn', methods: ['POST'])]
    public function singIn(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $emailUsername = Users::validate($data['emailUsername']);
        $password = Users::validate($data['password']);

        if (empty($emailUsername) || empty($password)) {
            return $this->json(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::passwordsMatch($emailUsername, $password, $entityManager)) {
            return $this->json(['error' => 'User or password doesnt match'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => 'Session successfully started'], Response::HTTP_OK);
    }

    #[Route('/deleteUser/{id<\d+>}', 'api_deleteUser', methods: ['DELETE', 'GET'])]
    public function deleteUser(EntityManagerInterface $entityManager, $id): JsonResponse
    {
        $delUser = $entityManager->find(Users::class, $id);

        if (!isset($delUser)) {
            return $this->json(['error' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($delUser);
        $entityManager->flush();


        return $this->json(['success' => 'User successfully deleted'], Response::HTTP_CREATED);
    }

    #[Route('/modifyUser/{id<\d+>}', 'api_modifyUser', methods: ['PUT', 'POST'])]
    public function modifyUser(EntityManagerInterface $entityManager, Request $request, $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $username = Users::validate($data['username']);
        $password = Users::validate($data['password']);
        $role = Users::validate($data['role']);

        if (empty($username)) {
            return $this->json(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->find(Users::class, $id);

        //!VER TEMA COMPROBAR QUE EL ID QUE SE PASA POR LA URL SEA EL MISMO QUE EL DEL USUARIO QUE LO SOLICITA SOLO PARA LOS USUARIOS NO SE PUEDE HACER CON EL ADMIN YA QUE EL PUEDE MODIFICAR LOS PERFILES DE LOS USUARIOS POR EJEMPLO PARA MODIFICAR EL ROLE

        if (!$user) {
            return $this->json(['error' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = Users::hashPassword($password);

        $user->setUsername($username);
        if (!empty($password)) {
            $user->setPassword($hashedPassword);
        }

        if (!empty($role)) {
            if (!in_array($role, ['ADMIN', 'USER', 'COACH'])) {
                return $this->json(['error' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
            }
            $user->setRole($role);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['success' => 'User successfully updated'], Response::HTTP_CREATED);
    }
}
