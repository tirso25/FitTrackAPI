<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

//!SECURIZAR EN CUANTO A role

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
                'role' => $user->getRole(),
                'active' => $user->getActive(),
                'date_union' => $user->getDateUnion()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/seeOneUser/{id<\d+>}', name: 'api_seeOneUser', methods: ['GET'])]
    public function seeOneUser(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        session_start();
        $id_user = $_SESSION["id_user"];

        $user = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $id]);
        $thisUser = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $id_user]);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data = [];

        $role_user = $thisUser->getRole();
        //!!SE PODRÍA USAR PARA QUE LOS USUARIOS PUEDAN VER LOS PERFILES DE OTROS

        //!HABLAR SOBRE SI ES BUENA IDEA MANDAR EL ID O Q SOLO SE ENVIE EL NOMBRE Y LUEGO SI SE NECESITA EL ID HACER UN findOneBy CON EL username
        if ($role_user === "ROLE_USER" && $id_user !== $id) {
            $data[] = [
                'id_usr' => $user->getIdUsr(),
                'username' => $user->getUsername()
            ];
        } else {
            $data[] = [
                'id_usr' => $user->getIdUsr(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'password' => $user->getPassword(),
                'role' => $user->getRole(),
                'active' => $user->getActive(),
                'date_union' => $user->getDateUnion()
            ];
        }

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
            return $this->json(['type' => 'error', 'message' => 'User already exists'], Response::HTTP_BAD_REQUEST);
        }

        if ($password !== $repeatPassword) {
            return $this->json(['type' => 'error', 'message' => 'Passwords dont match'], Response::HTTP_BAD_REQUEST);
        }

        $newUser = new Users();

        $newUser->setEmail($email);
        $newUser->setUsername($username);
        $newUser->setPassword(Users::hashPassword($password));
        $newUser->setRole('ROLE_USER');
        $newUser->setActive(true);
        $newUser->setDateUnion(new \DateTime());

        $entityManager->persist($newUser);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'User successfully created'], Response::HTTP_CREATED);
    }

    #[Route('/singIn', name: 'api_singIn', methods: ['POST'])]
    public function singIn(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = Users::validate(strtolower($data['email']));
        $password = Users::validate($data['password']);
        $rememberme = isset($data['rememberme'])
            ? filter_var($data['rememberme'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
        $username_regex = "/^[a-z0-9]{5,20}$/";

        if (empty($email) || empty($password) || $rememberme === null) {
            return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (str_contains($email, '@')) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
                return $this->json(['type' => 'error', 'message' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            if (!preg_match($username_regex, $email)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid username format'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!preg_match($password_regex, $password)) {
            return $this->json(['type' => 'error', 'message' => 'Invalid password format'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::passwordsMatch($email, $password, $entityManager)) {
            return $this->json(['type' => 'error', 'message' => 'User or password doesnt match'], Response::HTTP_BAD_REQUEST);
        }

        $id_user = Users::getIdUser($email, $entityManager);

        if (!$id_user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        session_start();

        $_SESSION['id_user'] = $id_user;

        if ($rememberme === true) {
            $token = Users::generatorToken();

            Users::saveToken($entityManager, $id_user, $token);

            setcookie("token", $token, time() + (3600 * 24 * 30));
        } elseif ($rememberme == false) {
            Users::removeToken($entityManager, $id_user);
        }

        return $this->json(['type' => 'success', 'message' => 'Session successfully started'], Response::HTTP_OK);
    }

    #[Route('/signOut', name: 'api_signOut', methods: ['POST'])]
    public function signOut(EntityManagerInterface $entityManager): JsonResponse
    {
        session_start();

        Users::removeToken($entityManager, $_SESSION['id_user']);

        setcookie("token", "", time() - 3600);

        unset($_SESSION['id_user']);

        return $this->json(['type' => 'success', 'message' => 'Session successfully ended'], Response::HTTP_OK);
    }

    #[Route('/tokenExisting', name: 'app_tokenExisting', methods: ['POST'])]
    public function tokenExisting(EntityManagerInterface $entityManager): JsonResponse
    {
        if (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];

            $user = Users::tokenExisting($token, $entityManager);

            if ($user) {
                $_SESSION['id_user'] = $user->getIdUsr();
                $username = $user->getUsername();

                return $this->json(['type' => 'success', 'message' => "Welcome back $username!!!"]);
            }
        }
        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/deleteUser/{id<\d+>}', name: 'api_deleteUser', methods: ['DELETE', 'POST'])]
    public function deleteUser(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $delUser = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $id]);

        if (!$delUser) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $delUser->setActive(false);
        $entityManager->flush();


        return $this->json(['type' => 'success', 'message' => 'User successfully deleted'], Response::HTTP_CREATED);
    }

    #[Route('/modifyUser/{id<\d+>}', name: 'api_modifyUser', methods: ['PUT', 'POST', 'GET'])]
    public function modifyUser(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
    {
        session_start();
        $id_user = $_SESSION["id_user"];

        $user = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $id]);
        $thisUser = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $id_user]);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $role_user = $thisUser->getRole();

        if ($role_user === "ROLE_USER" && $id_user !== $id) {
            return $this->json(['type' => 'error', 'message' => 'The user does not match'], Response::HTTP_BAD_REQUEST);
        }

        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $data = json_decode($request->getContent(), true);

            $username = Users::validate(strtolower($data['username']));
            $password = Users::validate($data['password']);
            $role = Users::validate($data['role']);

            $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
            $username_regex = "/^[a-z0-9]{5,20}$/";
            $role_regex = "/^[A-Z0-9]{1,255}$/";

            if (empty($username)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
            }

            if (!preg_match($username_regex, $username)) {
                return $this->json(['type' => 'error', 'message' => 'Invalid username format'], Response::HTTP_BAD_REQUEST);
            }

            if (Users::userExisting2($id, $username, $entityManager)) {
                return $this->json(['type' => 'error', 'message' => 'User already exists'], Response::HTTP_BAD_REQUEST);
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
                if (!in_array($role, ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_COACH']) || !preg_match($role_regex, $role)) {
                    return $this->json(['type' => 'error', 'message' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
                }
                $user->setRole($role);
            }

            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'User successfully updated'], Response::HTTP_CREATED);
        } elseif ($request->isMethod('GET')) {
            $data[] = [
                'id_usr' => $user->getIdUsr(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'password' => $user->getPassword(),
                'role' => $user->getRole(),
                'roles' => ['ADMIN', 'USER', 'COACH']
            ];

            return $this->json($data, Response::HTTP_OK);
        } else {
            return $this->json(['type' => 'error', 'message' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
        }
    }

    #[Route('/whoami', name: 'app_whoami', methods: ['GET'])]
    public function whoami(): JsonResponse
    {
        session_start();
        return $this->json(['ID' => $_SESSION['id_user']]);
    }
}
