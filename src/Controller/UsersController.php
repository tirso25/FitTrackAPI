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

        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id_usr' => $user->getIdUsr(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'password' => $user->getPassword(),
                'role' => $user->getRole()
            ];
        }

        if (empty($data)) {
            return $this->json(['alert' => 'No users found'], Response::HTTP_OK);
        }

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

        $entityManager->persist($newUser);
        $entityManager->flush();

        return $this->json(['succes' => 'User successfully created'], Response::HTTP_CREATED);
    }

    // #[Route('/singIn', 'api_singIn', methods: ['POST'])]
    // public function singIn(EntityManagerInterface $entityManager, Request $request): JsonResponse
    // {
    //     $data = json_decode($request->getContent(), true);

    //     $email = Users::validate($data['email']);
    //     $password = Users::validate($data['password']);
    // }
}
