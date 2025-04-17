<?php

namespace App\Controller;

use App\Entity\FavoriteExercises;
use App\Entity\Roles;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


//!SECURIZAR EN CUANTO A role
//!VER NUEVOS CAMBIOS CON PUBLIC (perfil publico)

#[Route('/api/users')]
class UsersController extends AbstractController
{
    #[Route('/seeAllUsers', name: 'api_seeAllUsers', methods: ['GET'])]
    public function seeAllUsers(EntityManagerInterface $entityManager): JsonResponse
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
                'role' => $user->getRole()->getName(),
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

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $id_user = $_SESSION["id_user"];

        $user = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $id]);
        $thisUser = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $id_user]);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data = [];

        $role_user = $thisUser->getRole()->getName();

        //!HABLAR SOBRE SI ES BUENA IDEA MANDAR EL ID O Q SOLO SE ENVIE EL NOMBRE Y LUEGO SI SE NECESITA EL ID HACER UN findOneBy CON EL username
        if ($role_user === "ROLE_USER" && $id_user !== $id) {
            $favs = FavoriteExercises::getFavouriteExercisesByUserId($id, $entityManager);

            $data[] = [
                'id_usr' => $user->getIdUsr(),
                'username' => $user->getUsername(),
                'exercisesFavorites' => $favs
            ];
        } else {
            $data[] = [
                'id_usr' => $user->getIdUsr(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'password' => $user->getPassword(),
                'role' => $user->getRole()->getName(),
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

        $email = Users::validate(strtolower($data['email'] ?? ""));
        $username = Users::validate(strtolower($data['username'] ?? ""));
        $password = $data['password'] ?? "";
        $repeatPassword = $data['repeatPassword'] ?? "";

        $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
        $username_regex = "/^[a-z0-9]{5,20}$/";

        if ($email === "" || $username === "" || $password === "" || $repeatPassword === "") {
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

        $role = $entityManager->getRepository(Roles::class)->findOneBy(['name' => 'ROLE_USER']);

        $newUser = new Users();

        $newUser->setEmail($email);
        $newUser->setUsername($username);
        $newUser->setPassword(Users::hashPassword($password));
        $newUser->setRole($role);
        $newUser->setActive(true);
        $newUser->setPublic(true);
        $newUser->setDateUnion(new \DateTime());

        $entityManager->persist($newUser);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'User successfully created'], Response::HTTP_CREATED);
    }

    #[Route('/singIn', name: 'api_singIn', methods: ['POST'])]
    public function singIn(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = Users::validate(strtolower($data['email'] ?? ""));
        $password = Users::validate($data['password'] ?? "");
        $rememberme = isset($data['rememberme']) ? filter_var($data['rememberme'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
        $username_regex = "/^[a-z0-9]{5,20}$/";

        if ($email === "" || $password === "" || $rememberme === null) {
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

        $id_user = Users::getIdUser($email, $entityManager);

        if (!$id_user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::passwordsMatch($email, $password, $entityManager)) {
            return $this->json(['type' => 'error', 'message' => 'User or password doesnt match'], Response::HTTP_BAD_REQUEST);
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

    private function forceSignOut(EntityManagerInterface $entityManager, int $id_user)
    {
        Users::removeToken($entityManager, $id_user);

        setcookie("token", "", time() - 3600, "/");

        unset($_SESSION['id_user']);
    }

    #[Route('/singOut', name: 'api_signOut', methods: ['POST'])]
    public function signOut(EntityManagerInterface $entityManager): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $id_user = $_SESSION['id_user'];

        $this->forceSignOut($entityManager, $id_user);

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

    //!DEPENDIENDO DE LO QUE SE DIGA SE ÙEDE QUITAR PQ YA LO HACE modifyUser
    #[Route('/deleteUser/{id<\d+>}', name: 'api_deleteUser', methods: ['DELETE', 'PUT', 'POST'])]
    public function deleteUser(EntityManagerInterface $entityManager, int $id): JsonResponse
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

        $delUser = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $id]);

        if (!$delUser) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $delUser->setActive(false);
        $entityManager->flush();


        return $this->json(['type' => 'success', 'message' => 'User successfully deleted'], Response::HTTP_CREATED);
    }

    //!DEPENDIENDO DE LO QUE SE DIGA SE ÙEDE QUITAR PQ YA LO HACE modifyUser
    #[Route('/activeUser/{id<\d+>}', name: 'app_activeUser', methods: ['PUT', 'POST'])]
    public function activeUser(EntityManagerInterface $entityManager, int $id): JsonResponse
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

        $user = $entityManager->find(Users::class, $id);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $user->setActive(true);

        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'User successfully activated'], Response::HTTP_CREATED);
    }

    #[Route('/modifyUser/{id<\d+>}', name: 'api_modifyUser', methods: ['PUT', 'POST', 'GET'])]
    public function modifyUser(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $id]);
        $thisUser = $entityManager->getRepository(Users::class)->findOneBy(['id_usr' => $_SESSION['id_user']]);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $role_user = $thisUser->getRole()->getName();

        if ($role_user === "ROLE_USER" && $_SESSION['id_user'] !== $id) {
            return $this->json(['type' => 'error', 'message' => 'The user does not match'], Response::HTTP_BAD_REQUEST);
        }

        $roles = $entityManager->getRepository(Roles::class)->findAll();

        $rolesData = [];

        foreach ($roles as $data) {
            $rolesData[] = [
                'id' => $data->getIdRole(),
                'name' => $data->getName(),
            ];
        }

        if ($request->isMethod('GET')) {
            $data = [
                'id_usr' => $user->getIdUsr(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'public' => $user->getPublic(),
                'active' => $user->getActive(),
                'role_id' => $user->getRole()->getIdRole(),
                'role_name' => $user->getRole()->getName(),
                'roles' => $rolesData
            ];

            return $this->json($data, Response::HTTP_OK);
        }

        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $data = json_decode($request->getContent(), true);

            $username = Users::validate(strtolower($data['username'] ?? ""));
            $password = Users::validate($data['password']);
            $roleId = (int)Users::validate($data['role']) ?? "";
            $public = filter_var($data['public'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $active = filter_var($data['active'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
            $username_regex = "/^[a-z0-9]{5,20}$/";

            if ($username === "" || !isset($password) || $roleId === "" || $public === null || $active === null) {
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

            if ($public !== null) {
                $user->setPublic($public);
            }

            if ($role_user === "ROLE_ADMIN") {

                if (!empty($roleId)) {
                    $role = $entityManager->find(Roles::class, $roleId);

                    if (!$role) {
                        return $this->json(['type' => 'error', 'message' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
                    }

                    $user->setRole($role);
                }

                if ($active !== null) {
                    $user->setActive($active);
                }
            }

            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'User successfully updated'], Response::HTTP_CREATED);
        }

        return $this->json(['type' => 'error', 'message' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[Route('/whoami', name: 'app_whoami', methods: ['GET'])]
    public function whoami(EntityManagerInterface $entityManager): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (!Users::checkState($entityManager, $_SESSION['id_user'])) {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class,  $_SESSION['id_user']);
        $role = $thisUser->getRole()->getName();

        return $this->json(['ID' =>  $_SESSION['id_user'], 'ROLE' => $role]);
    }
}
