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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;

//!SECURIZAR EN CUANTO A role

#[Route('/api/users')]
class UsersController extends AbstractController
{
    private function forceSignOut(EntityManagerInterface $entityManager, int $id_user)
    {
        Users::removeToken($entityManager, $id_user);

        setcookie("token", "", time() - 3600, "/");

        unset($_SESSION['id_user']);
    }

    //!CON JS AL DEVOLVER UN JSON CON EL active SE PUEDE FILTAR EN EL FRONT POR active SIN NECESIDAD DE CREAR UN METODO DE seeAllActiveUsers Y QUITARNIOS EL RECARGAR LA PÁGINA PUDIENDIO HACER UN Switches PARA ALTERNAR ENTRE ACTIVOS O TODOS
    #[Route('/seeAllUsers', name: 'api_seeAllUsers', methods: ['GET'])]
    public function seeAllUsers(EntityManagerInterface $entityManager): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (Users::checkState($entityManager, $_SESSION['id_user']) !== "active") {
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
                'status' => $user->getStatus(),
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

        if (Users::checkState($entityManager, $_SESSION['id_user']) !== "active") {
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
                'status' => $user->getStatus(),
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
        $newUser->setStatus('pending');
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

        $user = Users::getIdUser($email, $entityManager);
        $state_user = $user->getStatus();

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $id_user = $user->getIdUsr();

        //! https://codepen.io/pen Link para usar el sweetalert, para cuando el usuario no verifica el código
        switch ($state_user) {
            case "pending":
                return $this->json(['type' => 'warning', 'message' => 'This user is pending activation'], Response::HTTP_BAD_REQUEST);
            case "deleted":
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

    #[Route('/singOut', name: 'api_signOut', methods: ['POST'])]
    public function signOut(EntityManagerInterface $entityManager): JsonResponse
    {
        session_start();

        if (!$_SESSION['id_user']) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if (Users::checkState($entityManager, $_SESSION['id_user']) !== "active") {
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

            $user = $entityManager->getRepository(Users::class)->findOneBy(['token' => $token]);;

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

        if (Users::checkState($entityManager, $_SESSION['id_user']) !== "active") {
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

        $delUser->setStatus('deleted');
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

        if (Users::checkState($entityManager, $_SESSION['id_user']) !== "active") {
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

        $user->setStatus('active');

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

        if (Users::checkState($entityManager, $_SESSION['id_user']) !== "active") {
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

        if (!$roles) {
            return $this->json(['type' => 'warning', 'message' => 'No roles found'], Response::HTTP_OK);
        }

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
                'status' => $user->getStatus(),
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
            $public = array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null;
            $status = Users::validate($data['status'] ?? null);

            $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
            $username_regex = "/^[a-z0-9]{5,20}$/";

            if ($username === "" || !isset($password) || $roleId === "" || $public === null || $status === null) {
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
            }

            if (in_array($status, ['active', 'deleted'])) {
                $user->setActive($status);
            } else {
                return $this->json(['type' => 'error', 'message' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
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

        if (Users::checkState($entityManager, $_SESSION['id_user']) !== "active") {
            $this->forceSignOut($entityManager, $_SESSION['id_user']);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class,  $_SESSION['id_user']);

        return $this->json(['ID' =>  $thisUser->getIdUsr(), 'USERNAME' => $thisUser->getUsername(), 'ROLE' => $thisUser->getRole()->getName()]);
    }

    //!https://codepen.io/pen <- Link para la notificación para poner el email
    #[Route('/sendEmail', name: 'app_activeUser', methods: ['POST'])]
    public function sendEmail(EntityManagerInterface $entityManager, MailerInterface $mailer, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = Users::validate(strtolower($data['email'] ?? ""));

        if ($email === "") {
            return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            return $this->json(['type' => 'error', 'message' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $verificationCode = random_int(100000, 999999);

        $user->setVerificationCode($verificationCode);

        $entityManager->flush();

        $sendEmail = (new Email())
            ->from('fittracktfg@gmail.com')
            ->to($email)
            ->subject('Bienvenido a FitTrack')
            ->html(
                '<html>
                <body>
                    <h1>¡Gracias por registrarte en FitTrack!</h1>
                    <p>Nos alegra que te hayas unido a nuestra comunidad.</p>
                    <p>Aquí tienes tu codigo de verificación:</p>
                    <h2>' . $verificationCode . '</h2>
                    <p>¡Que disfrutes de la app!</p>
                    <img src="cid:fittrack_logo" alt="Logo de FitTrack" style="width: 250px; height: auto;"/>
                </body>
            </html>'
            )
            ->embedFromPath('assets/img/FTLogo.png', 'fittrack_logo');

        $mailer->send($sendEmail);

        return $this->json(['type' => 'error', 'message' => 'Email sent successfully'], Response::HTTP_OK);
    }

    #[Route('/checkCode', name: 'api_checkCode', methods: ['POST'])]
    public function checkCode(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $verificationCode = (int)Users::validate($data['verificationCode']) ?? "";

        if ($verificationCode === "") {
            return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $code = $entityManager->getRepository(Users::class)->findOneBy(['verification_code' => $verificationCode]);

        if (!$code) {
            return $this->json(['type' => 'error', 'message' => 'Invalid verification code'], Response::HTTP_BAD_REQUEST);
        }

        $code->setVerificationCode(null);
        $code->setStatus('active');

        $entityManager->persist($code);
        $entityManager->flush();

        return $this->json(['type' => 'success', 'message' => 'User successfully activated'], Response::HTTP_CREATED);
    }
}