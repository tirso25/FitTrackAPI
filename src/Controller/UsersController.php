<?php

namespace App\Controller;

use App\Entity\Roles;
use App\Entity\Users;
use App\Service\FavoritesExercisesService;
use App\Service\GlobalService;
use App\Service\RoleService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

#[Route('/api/users')]
class UsersController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private GlobalService $globalService,
        private FavoritesExercisesService $favoriteExercisesService,
        private RoleService $roleService,
    ) {}

    //!CON JS AL DEVOLVER UN JSON CON EL active SE PUEDE FILTAR EN EL FRONT POR active SIN NECESIDAD DE CREAR UN METODO DE seeAllActiveUsers Y QUITARNIOS EL RECARGAR LA PÁGINA PUDIENDIO HACER UN Switches PARA ALTERNAR ENTRE ACTIVOS O TODOS
    #[Route('/seeAllUsers', name: 'api_seeAllUsers', methods: ['GET'])]
    public function seeAllUsers(EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        $idUser = $session->get('user_id');

        if (!$idUser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userService->checkState($entityManager, $idUser) !== "active") {
            $this->globalService->forceSignOut($entityManager, $idUser, $session);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class, $idUser);
        $role = $thisUser->getRole()->getName();

        if ($role !== "ROLE_ADMIN") {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $users = $this->userService->seeAllUsers($entityManager);

        if (!$users) {
            return $this->json(['type' => 'warning', 'message' => 'No users found'], Response::HTTP_OK);
        }

        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id_usr' => $user->getUserId(),
                'email' => $user->getEmail(),
                'username' => $user->getDisplayUsername(),
                'description' => $user->getDescription(),
                'role' => $user->getRole()->getName(),
                'status' => $user->getStatus(),
                'date_union' => $user->getDateUnion()
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/seeOneUser/{id<\d+>}', name: 'api_seeOneUser', methods: ['GET'])]
    public function seeOneUser(EntityManagerInterface $entityManager, int $id, SessionInterface $session): JsonResponse
    {
        $idUser = $session->get('user_id');

        if (!$idUser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userService->checkState($entityManager, $idUser) !== "active") {
            $this->globalService->forceSignOut($entityManager, $idUser, $session);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(Users::class)->findOneBy(['user_id' => $id]);
        $thisUser = $entityManager->getRepository(Users::class)->findOneBy(['user_id' => $idUser]);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $data = [];

        $role_user = $thisUser->getRole()->getName();

        if ($role_user !== "ROLE_ADMIN" && $idUser !== $id) {
            $favs = $this->favoriteExercisesService->getFavouriteExercisesByUserId($id, $entityManager);

            $data[] = [
                'id_usr' => $user->getUserId(),
                'username' => $user->getDisplayUsername(),
                'description' => $user->getDescription(),
                'exercisesFavorites' => $favs
            ];
        } else {
            $data[] = [
                'id_usr' => $user->getUserId(),
                'email' => $user->getEmail(),
                'username' => $user->getDisplayUsername(),
                'description' => $user->getDescription(),
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
        try {
            $data = json_decode($request->getContent(), true);

            $email = $this->globalService->validate(strtolower($data['email'] ?? ""));
            $username = $this->globalService->validate(strtolower($data['username'] ?? ""));
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

            if ($this->userService->userExisting3($email, $username, $entityManager)) {
                return $this->json(['type' => 'error', 'message' => 'User already exists'], Response::HTTP_BAD_REQUEST);
            }

            if ($password !== $repeatPassword) {
                return $this->json(['type' => 'error', 'message' => 'Passwords dont match'], Response::HTTP_BAD_REQUEST);
            }

            $role = $entityManager->find(Roles::class, 3);

            $newUser = new Users();

            $newUser->setEmail($email);
            $newUser->setUsername($username);
            $newUser->setPassword($this->userService->hashPassword($password));
            $newUser->setRole($role);
            $newUser->setStatus('pending');
            $newUser->setPublic(true);
            $newUser->setDateUnion(new \DateTime());


            $entityManager->persist($newUser);
            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'User successfully created'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while singUp the user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/singIn', name: 'api_singIn', methods: ['POST'])]
    public function singIn(EntityManagerInterface $entityManager, Request $request, SessionInterface $session, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $email = $this->globalService->validate(strtolower($data['email'] ?? ""));
            $password = $this->globalService->validate($data['password'] ?? "");
            $rememberme = isset($data['rememberme']) ? filter_var($data['rememberme'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

            $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
            $username_regex = "/^[a-z0-9]{4,20}$/";

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

            $user = $this->userService->userExisting($email, $entityManager);

            if (!$user) {
                return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
            }

            $state_user = $user->getStatus();
            $id_user = $user->getUserId();

            switch ($state_user) {
                case "pending":
                    return $this->json(['type' => 'warning', 'message' => 'This user is pending activation'], Response::HTTP_BAD_REQUEST);
                case "deleted":
                    return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
            }

            if ($user->getRole()->getRoleId() !== 1 && $user->getRole()->getRoleId() !== 2) {
                $query = $entityManager->createQuery(
                    'SELECT u.password FROM App\Entity\Users u WHERE u.email = :email OR u.username = :username'
                )->setParameters(['email' => $email, 'username' => $email]);

                $hashedPassword = $query->getSingleScalarResult();

                $passwordVerify = password_verify($password, $hashedPassword);

                if (!$passwordVerify) {
                    return $this->json(['type' => 'error', 'message' => 'User or password doesnt match'], Response::HTTP_BAD_REQUEST);
                }
            }

            $jwtToken = $jwtManager->create($user);

            $session->set('user_id', $id_user);
            $idUser = $session->get('user_id');

            if ($rememberme === true) {
                $rememberToken  = bin2hex(random_bytes(32));

                $user->setToken($rememberToken);

                setcookie("rememberToken", $rememberToken, time() + (3600 * 24 * 30), "/", "", true, true);
            } elseif ($rememberme == false) {
                $this->userService->removeToken($entityManager, $idUser);
            }

            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Session successfully started', 'token' => $jwtToken], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while singIn the user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //!BORRAR EL JWT DEL LOCALSTORAGE 
    #[Route('/singOut', name: 'api_signOut', methods: ['POST'])]
    public function signOut(EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        $idUser = $session->get('user_id');

        if (!$idUser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userService->checkState($entityManager, $idUser) !== "active") {
            $this->globalService->forceSignOut($entityManager, $idUser, $session);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $id_user = $idUser;

        $this->globalService->forceSignOut($entityManager, $id_user, $session);

        return $this->json(['type' => 'success', 'message' => 'Session successfully ended'], Response::HTTP_OK);
    }

    #[Route('/tokenExisting', name: 'app_tokenExisting', methods: ['POST'])]
    public function tokenExisting(EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        if (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];

            $user = $entityManager->getRepository(Users::class)->findOneBy(['token' => $token]);

            if ($user) {
                $id_user = $user->getUserId();

                $session->set('user_id', $id_user);

                $username = $user->getDisplayUsername();

                return $this->json(['type' => 'success', 'message' => "Welcome back $username!!!"]);
            }
        }
        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    //!DEPENDIENDO DE LO QUE SE DIGA SE ÙEDE QUITAR PQ YA LO HACE modifyUser
    #[Route('/deleteUser/{id<\d+>}', name: 'api_deleteUser', methods: ['DELETE'])]
    public function deleteUser(EntityManagerInterface $entityManager, int $id, SessionInterface $session): JsonResponse
    {
        try {
            $idUser = $session->get('user_id');

            if (!$idUser) {
                return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
            }

            if ($this->userService->checkState($entityManager, $idUser) !== "active") {
                $this->globalService->forceSignOut($entityManager, $idUser, $session);

                return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
            }

            $thisUser = $entityManager->find(Users::class, $idUser);
            $roleThisUser = $thisUser->getRole()->getName();

            if ($roleThisUser !== "ROLE_ADMIN" && $roleThisUser !== "ROLE_ROOT" && $thisUser->getUserId() != $id) {
                return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
            }

            $delUser = $entityManager->find(Users::class, $id);
            $roleDelUser = $delUser->getRole()->getName();

            if (!$delUser) {
                return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
            }

            if ($roleThisUser !== "ROLE_ROOT" && $roleDelUser === "ROLE_ADMIN") {
                return $this->json(['type' => 'error', 'message' => 'Only root users can delete administrators'], Response::HTTP_BAD_REQUEST);
            }

            $delUser->setStatus('deleted');
            $entityManager->flush();


            return $this->json(['type' => 'success', 'message' => 'User successfully deleted'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while deleting the user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //!DEPENDIENDO DE LO QUE SE DIGA SE ÙEDE QUITAR PQ YA LO HACE modifyUser
    #[Route('/activeUser/{id<\d+>}', name: 'app_activeUser', methods: ['PUT'])]
    public function activeUser(EntityManagerInterface $entityManager, int $id, SessionInterface $session): JsonResponse
    {
        try {
            $idUser = $session->get('user_id');
            if (!$idUser) {
                return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
            }

            if ($this->userService->checkState($entityManager, $idUser) !== "active") {
                $this->globalService->forceSignOut($entityManager, $idUser, $session);

                return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
            }

            $thisUser = $entityManager->find(Users::class, $idUser);
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
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while activating the user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/modifyUser/{id<\d+>}', name: 'api_modifyUser', methods: ['PUT', 'GET'])]
    public function modifyUser(EntityManagerInterface $entityManager, Request $request, int $id, SessionInterface $session): JsonResponse
    {
        $idUser = $session->get('user_id');

        if (!$idUser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userService->checkState($entityManager, $idUser) !== "active") {
            $this->globalService->forceSignOut($entityManager, $idUser, $session);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->find(Users::class, $id);
        $thisUser = $entityManager->find(Users::class, $idUser);
        $roleModifyUser = $user->getRole()->getName();

        if ($roleModifyUser === "ROLE_ROOT") {
            return $this->json(['type' => 'error', 'message' => 'You cannot modify root users'], Response::HTTP_BAD_REQUEST);
        }

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        $role_user = $thisUser->getRole()->getName();

        if ($role_user === "ROLE_USER" && $idUser  !== $id) {
            return $this->json(['type' => 'error', 'message' => 'The user does not match'], Response::HTTP_BAD_REQUEST);
        }

        $roles = $this->roleService->seeAllRoles($entityManager);

        if (!$roles) {
            return $this->json(['type' => 'warning', 'message' => 'No roles found'], Response::HTTP_OK);
        }

        $rolesData = [];

        foreach ($roles as $data) {
            $rolesData[] = [
                'id' => $data->getRoleId(),
                'name' => $data->getName(),
            ];
        }

        if ($request->isMethod('GET')) {
            $data = [
                'id_usr' => $user->getUserId(),
                'username' => $user->getDisplayUsername(),
                'description' => $user->getDescription(),
                'public' => $user->getPublic(),
                'status' => $user->getStatus(),
                'role_id' => $user->getRole()->getRoleId(),
                'role_name' => $user->getRole()->getName(),
                'roles' => $rolesData
            ];

            return $this->json($data, Response::HTTP_OK);
        }

        if ($request->isMethod('PUT')) {
            try {
                $data = json_decode($request->getContent(), true);

                $username = $this->globalService->validate(strtolower($data['username'] ?? ""));
                $password = $this->globalService->validate($data['password']);
                $roleId = (int)$this->globalService->validate($data['role_id']) ?? "";
                $public = array_key_exists('public', $data)
                    ? filter_var($data['public'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null;
                $status = $this->globalService->validate($data['status'] ?? null);
                $description = $this->globalService->validate($data['description']) ?? "";

                $password_regex = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{5,}$/";
                $username_regex = "/^[a-z0-9]{5,20}$/";
                $description_regex = "/^[a-zA-Z0-9]{10,500}$/";

                if ($username === "" || !isset($password) || $roleId === "" || $public === null || $status === null) {
                    return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
                }

                if (!preg_match($username_regex, $username)) {
                    return $this->json(['type' => 'error', 'message' => 'Invalid username format'], Response::HTTP_BAD_REQUEST);
                }

                if ($this->userService->userExisting2($id, $username, $entityManager)) {
                    return $this->json(['type' => 'error', 'message' => 'User already exists'], Response::HTTP_BAD_REQUEST);
                }

                $user->setUsername($username);

                if (!empty($password)) {
                    if (!preg_match($password_regex, $password)) {
                        return $this->json(['type' => 'error', 'message' => 'Invalid password format'], Response::HTTP_BAD_REQUEST);
                    }

                    $hashedPassword = $this->userService->hashPassword($password);
                    $user->setPassword($hashedPassword);
                }

                if (!empty($description)) {
                    if (!preg_match($description_regex, $description)) {
                        return $this->json(['type' => 'error', 'message' => 'Invalid description format'], Response::HTTP_BAD_REQUEST);
                    }
                    $user->setDescription($description);
                } else {
                    $user->setDescription(null);
                }

                if ($public !== null) {
                    $user->setPublic($public);
                }

                if ($role_user === "ROLE_ADMIN" || $role_user === "ROLE_ROOT") {
                    if (!empty($roleId)) {
                        $role = $this->roleService->roleExisting($roleId, $entityManager);

                        if (!$role) {
                            return $this->json(['type' => 'error', 'message' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
                        }

                        if ($role_user !== "ROLE_ROOT" && $roleModifyUser === "ROLE_ADMIN") {
                            return $this->json(['type' => 'error', 'message' => 'Only root users can delete administrators'], Response::HTTP_BAD_REQUEST);
                        }

                        $user->setRole($role);
                    }
                }

                if (in_array($status, ['active', 'deleted'])) {
                    $user->setStatus($status);
                } else {
                    return $this->json(['type' => 'error', 'message' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
                }
                $entityManager->flush();

                return $this->json(['type' => 'success', 'message' => 'User successfully updated'], Response::HTTP_CREATED);
            } catch (\Exception $e) {
                return $this->json(['type' => 'error', 'message' => 'An error occurred while modifying the user'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return $this->json(['type' => 'error', 'message' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[Route('/whoami', name: 'app_whoami', methods: ['GET'])]
    public function whoami(EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        $idUser = $session->get('user_id');

        if (!$idUser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userService->checkState($entityManager, $idUser) !== "active") {
            $this->globalService->forceSignOut($entityManager, $idUser, $session);

            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_BAD_REQUEST);
        }

        $thisUser = $entityManager->find(Users::class,  $idUser);

        return $this->json(['ID' =>  $thisUser->getUserId(), 'USERNAME' => $thisUser->getDisplayUsername(), 'ROLE' => $thisUser->getRole()->getName()]);
    }

    #[Route('/sendEmail', name: 'app_activeUser', methods: ['POST'])]
    public function sendEmail(EntityManagerInterface $entityManager, MailerInterface $mailer, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $email = $this->globalService->validate(strtolower($data['email'] ?? ""));

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

            if ($user->getStatus() !== "pending") {
                return $this->json(['type' => 'error', 'message' => 'The user is already active'], Response::HTTP_BAD_REQUEST);
            }

            $verificationCode = random_int(100000, 999999);

            $user->setVerificationCode($verificationCode);

            $entityManager->flush();

            $sendEmail = (new Email())
                ->from('fittracktfg@gmail.com')
                ->to($email)
                ->subject('Welcome to FitTrack')
                ->html(
                '<html>
                    <body style="font-family: Arial, sans-serif; background-color: #f0fff0; margin: 0; padding: 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                            <tr>
                                <td align="center">
                                    <img src="cid:fittrack_logo" alt="FitTrack logo" style="width: 200px; height: auto; margin-bottom: 20px;" />
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h1 style="color: #2e7d32; text-align: center;">Welcome to FitTrack!</h1>
                                    <p style="color: #333333; font-size: 16px; text-align: center;">
                                        Thank you for registering with <strong>FitTrack</strong>.<br />
                                        We are delighted to have you join our community.
                                    </p>
                                    <p style="color: #333333; font-size: 16px; text-align: center;">
                                        Here is your verification code:
                                    </p>
                                    <h2 style="color: #4caf50; text-align: center;">' . $verificationCode . '</h2>
                                    <p style="color: #333333; font-size: 16px; text-align: center;">
                                        Enjoy the app and reach your goals in a smarter way!
                                    </p>
                                    <div style="text-align: center; margin-top: 30px;">
                                        <a href="https://fittrack.com" style="background-color: #4caf50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                                            Go to FitTrack
                                        </a>
                                    </div>
                                    <p style="text-align: center; color: #999999; font-size: 12px; margin-top: 30px;">
                                        &copy; ' . date("Y") . ' FitTrack. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </body>
                </html>'
                )
                ->embedFromPath('assets/img/FTLogo.png', 'fittrack_logo');

            $mailer->send($sendEmail);

            return $this->json(['type' => 'error', 'message' => 'Email sent successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error has occurred with the verification code'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/checkCode', name: 'api_checkCode', methods: ['POST'])]
    public function checkCode(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $verificationCode = (int)$this->globalService->validate($data['verificationCode']) ?? "";

            if ($verificationCode === "") {
                return $this->json(['type' => 'error', 'message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
            }

            if ($verificationCode > 999999 || $verificationCode < 100000) {
                return $this->json(['type' => 'error', 'message' => 'Invalid verification code format'], Response::HTTP_BAD_REQUEST);
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
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error has occurred with the verification code'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}