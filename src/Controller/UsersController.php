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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use OpenApi\Annotations as OA;

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
    public function seeAllUsers(EntityManagerInterface $entityManager): JsonResponse
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
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        if ($thisuserRole !== 'ROLE_ADMIN') {
            return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
        }

        $users = $this->userService->seeAllUsers($entityManager);

        if (!$users) {
            return $this->json(['type' => 'warning', 'message' => 'No users found'], Response::HTTP_BAD_REQUEST);
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
    /**
     * @OA\Get(
     *     path="/api/users/seeOneUser/{id}",
     *     summary="Get user details by ID",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", format="int64", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(ref="#/components/schemas/FullUserDetails"),
     *                 @OA\Schema(ref="#/components/schemas/PublicUserDetails")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="You are not logged in")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="You are not active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="The user does not exist")
     *         )
     *     )
     * )
     */
    public function seeOneUser(EntityManagerInterface $entityManager, int $id)
    {
        /** @var \App\Entity\Users $thisuser */
        $thisuser = $this->getUser();
        $thisuserId = $thisuser->getUserId();
        $thisuserRole = $thisuser->getRole()->getName();
        $thisuserStatus = $thisuser->getStatus();

        if (!$thisuser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuserStatus !== 'active') {
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        if ($id === 1) {
            return $this->json(['type' => 'warning', 'message' => 'The user is not available'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(Users::class)->findOneBy(['user_id' => $id]);

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        if ($user->getStatus() === "pending") {
            return $this->json(['type' => 'error', 'message' => 'The user is pending activation'], Response::HTTP_BAD_REQUEST);
        }

        if ($user->getRole() === 1) {
            return $this->json(['type' => 'warning', 'message' => 'The user is not available'], Response::HTTP_BAD_REQUEST);
        }

        $data = [];
        $favs = $this->favoriteExercisesService->getFavouriteExercisesByUserId($id, $entityManager);
        if ($thisuserRole !== "ROLE_ADMIN" && $thisuserId  !== $id) {
            $data[] = [
                'id_usr' => $user->getUserId(),
                'username' => $user->getDisplayUsername(),
                'description' => $user->getDescription(),
                'date_union' => $user->getDateUnion(),
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
                'date_union' => $user->getDateUnion(),
            ];
        }

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/signUp', name: 'api_signUp', methods: ['POST'])]
    public function signUp(EntityManagerInterface $entityManager, Request $request): JsonResponse
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

    #[Route('/signIn', name: 'api_signIn', methods: ['POST'])]
    /**
     * @Route("/signIn", name="api_signIn", methods={"POST"})
     * 
     * @OA\Post(
     *     path="/api/users/signIn",
     *     summary="User login",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Login credentials",
     *         @OA\JsonContent(
     *             required={"email", "password", "rememberme"},
     *             @OA\Property(property="email", type="string", description="Email or username", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=5, example="SecurePass123!"),
     *             @OA\Property(property="rememberme", type="boolean", description="Keep session active for 30 days", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful authentication",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Session successfully started"),
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6...")
     *         ),
     *         @OA\Header(
     *             header="Set-Cookie",
     *             description="Remember token cookie",
     *             @OA\Schema(type="string", example="rememberToken=abc123; Path=/; Secure; HttpOnly")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="type", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Invalid credentials format")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="type", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Password does not meet requirements")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication failure",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="type", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Invalid credentials")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="type", type="string", example="warning"),
     *                     @OA\Property(property="message", type="string", example="Account pending activation")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Account status issue",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Account disabled")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal authentication error")
     *         )
     *     )
     * )
     */
    public function signIn(EntityManagerInterface $entityManager, Request $request, JWTTokenManagerInterface $jwtManager, JWTEncoderInterface $jwtEncoder): JsonResponse
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

            if ($rememberme === true) {
                $payload = [
                    'username' => $user->getUserIdentifier(),
                    'roles' => $user->getRoles(),
                    'exp' => time() + (3600 * 24 * 30),
                ];

                $jwtToken = $jwtEncoder->encode($payload);

                $rememberToken  = bin2hex(random_bytes(32));

                $user->setToken($rememberToken);

                setcookie("rememberToken", $rememberToken, time() + (3600 * 24 * 30));
            } elseif ($rememberme == false) {
                $jwtToken = $jwtManager->create($user);

                $this->userService->removeToken($entityManager, $id_user);
            }

            $entityManager->flush();

            return $this->json(['type' => 'success', 'message' => 'Session successfully started', 'token' => $jwtToken], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['type' => 'error', 'message' => 'An error occurred while singIn the user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    //!BORRAR EL JWT DEL LOCALSTORAGE 
    #[Route('/signOut', name: 'api_signOut', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/users/signOut",
     *     summary="Terminate user session",
     *     description="Invalidates current session and removes authentication tokens",
     *     tags={"Auth"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Session successfully closed",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Session successfully ended")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Not authenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Inactive account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Could not terminate session")
     *         )
     *     )
     * )
     */
    public function signOut(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\Users $thisuser */
        $thisuser = $this->getUser();
        $thisuserId = $thisuser->getUserId();
        $thisuserStatus = $thisuser->getStatus();

        if (!$thisuser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuserStatus !== 'active') {
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
            $this->globalService->forceSignOut($entityManager, $thisuserId);
        }

        $this->globalService->forceSignOut($entityManager, $thisuserId);

        return $this->json(['type' => 'success', 'message' => 'Session successfully ended'], Response::HTTP_OK);
    }

    #[Route('/tokenExisting', name: 'app_tokenExisting', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/users/tokenExisting",
     *     summary="Check persistent login token",
     *     description="Verifies 'remember-me' token from cookies to restore user session",
     *     tags={"Auth"},
     *     @OA\Response(
     *         response=200,
     *         description="Valid token found",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Welcome back fit_user123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="No valid token found"
     *     )
     * )
     */
    public function tokenExisting(EntityManagerInterface $entityManager): JsonResponse
    {
        if (isset($_COOKIE['rememberToken'])) {
            $token = $_COOKIE['rememberToken'];

            $user = $entityManager->getRepository(Users::class)->findOneBy(['token' => $token]);

            if ($user) {
                $username = $user->getDisplayUsername();

                return $this->json(['type' => 'success', 'message' => "Welcome back $username!!!"]);
            }
        }
        return $this->json(null, JsonResponse::HTTP_BAD_REQUEST);
    }

    //!DEPENDIENDO DE LO QUE SE DIGA SE ÙEDE QUITAR PQ YA LO HACE modifyUser
    #[Route('/deleteUser/{id<\d+>}', name: 'api_deleteUser', methods: ['DELETE'])]
    /**
     * @Route("/deleteUser/{id<\d+>}", name="api_deleteUser", methods={"DELETE"})
     *
     * @OA\Delete(
     *     path="/deleteUser/{id}",
     *     summary="Eliminar un usuario por ID",
     *     description="Permite a los administradores eliminar (estado 'deleted') a un usuario existente por su ID. Solo accesible para usuarios autenticados con rol de administrador y estado activo.",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del usuario a eliminar",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario eliminado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User successfully deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Datos incorrectos o no tiene permisos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="The user does not exist")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="El usuario no está activo o no autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="You are not active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno al intentar eliminar el usuario",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the user")
     *         )
     *     )
     * )
     */
    public function deleteUser(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        try {
            /** @var \App\Entity\Users $thisuser */
            $thisuser = $this->getUser();
            $thisuserRole = $thisuser->getRole()->getName();
            $thisuserStatus = $thisuser->getStatus();

            if (!$thisuser) {
                return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
            }

            if ($thisuserStatus !== 'active') {
                return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
                $this->globalService->forceSignOut($entityManager, $thisuserRole);
            }

            if ($thisuserRole !== 'ROLE_ADMIN') {
                return $this->json(['type' => 'error', 'message' => 'You are not an administrator'], Response::HTTP_BAD_REQUEST);
            }

            $delUser = $entityManager->find(Users::class, $id);
            $roleDelUser = $delUser->getRole()->getName();

            if (!$delUser) {
                return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
            }

            if ($thisuserRole !== 'ROLE_ADMIN' && $roleDelUser === "ROLE_ADMIN") {
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
    /**
     * @OA\Put(
     *     path="/api/users/activeUser/{id}",
     *     summary="Activate a user account",
     *     description="Allows administrators to activate a pending user account",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to activate",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User activated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User successfully activated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Authentication required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="type", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Insufficient privileges")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="type", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Account not active")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Could not activate user")
     *         )
     *     )
     * )
     */
    public function activeUser(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        try {
            /** @var \App\Entity\Users $thisuser */
            $thisuser = $this->getUser();
            $thisuserRole = $thisuser->getRole()->getName();
            $thisuserStatus = $thisuser->getStatus();

            if (!$thisuser) {
                return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
            }

            if ($thisuserStatus !== 'active') {
                return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
                $this->globalService->forceSignOut($entityManager, $thisuserRole);
            }

            if ($thisuserRole !== 'ROLE_ADMIN') {
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
    /**
     * @OA\PathItem(
     *     path="/api/users/modifyUser/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     )
     * )
     */
    public function modifyUser(EntityManagerInterface $entityManager, Request $request, int $id,): JsonResponse
    {
        /** @var \App\Entity\Users $thisuser */
        $thisuser = $this->getUser();
        $thisuserId = $thisuser->getUserId();
        $thisuserRole = $thisuser->getRole()->getName();
        $thisuserStatus = $thisuser->getStatus();

        if (!$thisuser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuserStatus !== 'active') {
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
            $this->globalService->forceSignOut($entityManager, $thisuserRole);
        }

        $user = $entityManager->find(Users::class, $id);
        $roleModifyUser = $user->getRole()->getName();

        if ($roleModifyUser === "ROLE_ROOT") {
            return $this->json(['type' => 'error', 'message' => 'You cannot modify root users'], Response::HTTP_BAD_REQUEST);
        }

        if (!$user) {
            return $this->json(['type' => 'error', 'message' => 'The user does not exist'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuserRole === "ROLE_USER" && $thisuserId  !== $id) {
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
            /**
             * @OA\Get(
             *     summary="Get user data for modification",
             *     tags={"Users"},
             *     security={{"bearerAuth": {}}},
             *     @OA\Response(
             *         response=200,
             *         description="User data and available roles",
             *         @OA\JsonContent(ref="#/components/schemas/UserModificationData")
             *     ),
             *     @OA\Response(response=403, description="Forbidden"),
             *     @OA\Response(response=404, description="User not found")
             * )
             */
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
            /**
             * @OA\Put(
             *     summary="Update user data",
             *     tags={"Users"},
             *     security={{"bearerAuth": {}}},
             *     @OA\RequestBody(
             *         @OA\JsonContent(
             *             @OA\Property(property="username", type="string", minLength=5, maxLength=20),
             *             @OA\Property(property="password", type="string", nullable=true),
             *             @OA\Property(property="description", type="string", nullable=true, maxLength=500),
             *             @OA\Property(property="public", type="boolean"),
             *             @OA\Property(property="status", type="string", enum={"active", "deleted"}),
             *             @OA\Property(property="role_id", type="integer")
             *         )
             *     ),
             *     @OA\Response(
             *         response=200,
             *         description="User updated successfully",
             *         @OA\JsonContent(
             *             @OA\Property(property="type", type="string", example="success"),
             *             @OA\Property(property="message", type="string", example="User updated")
             *         )
             *     ),
             *     @OA\Response(response=400, description="Validation error"),
             *     @OA\Response(response=403, description="Forbidden"),
             *     @OA\Response(response=404, description="User not found"),
             *     @OA\Response(response=500, description="Internal error")
             * )
             */
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
                $description_regex = "/^[a-zA-Z0-9]{5,500}$/";

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

                if ($thisuserRole === "ROLE_ADMIN" || $thisuserRole === "ROLE_ROOT") {
                    if (!empty($roleId)) {
                        $role = $this->roleService->roleExisting($roleId, $entityManager);

                        if (!$role) {
                            return $this->json(['type' => 'error', 'message' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
                        }

                        if ($thisuserRole !== "ROLE_ROOT" && $roleModifyUser === "ROLE_ADMIN") {
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
    /**
     * @Route("/whoami", name="api_whoami", methods={"GET"})
     *
     * @OA\Get(
     *     path="/whoami",
     *     summary="Obtener información del usuario autenticado",
     *     description="Devuelve el ID, nombre de usuario y rol del usuario actualmente autenticado si está activo.",
     *     tags={"Users"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Información del usuario obtenida con éxito",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="ID", type="integer", example=5),
     *             @OA\Property(property="USERNAME", type="string", example="juanito98"),
     *             @OA\Property(property="ROLE", type="string", example="ROLE_USER")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="El usuario no está logueado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="You are not logged")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="El usuario no está activo",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="You are not active")
     *         )
     *     )
     * )
     */
    public function whoami(): JsonResponse
    {
        /** @var \App\Entity\Users $thisuser */
        $thisuser = $this->getUser();

        if (!$thisuser) {
            return $this->json(['type' => 'error', 'message' => 'You are not logged'], Response::HTTP_BAD_REQUEST);
        }

        if ($thisuser->getStatus() !== 'active') {
            return $this->json(['type' => 'error', 'message' => 'You are not active'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'ID' => $thisuser->getUserId(),
            'USERNAME' => $thisuser->getDisplayUsername(),
            'ROLE' => $thisuser->getRole()->getName(),
        ]);
    }

    #[Route('/sendEmail', name: 'app_activeUser', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/users/sendEmail",
     *     summary="Send verification email",
     *     description="Sends account activation email with verification code",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(
     *                 property="email", 
     *                 type="string", 
     *                 format="email", 
     *                 example="user@example.com"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification email sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Verification email sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="type", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="Invalid email format")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="type", type="string", example="error"),
     *                     @OA\Property(property="message", type="string", example="User already active")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Email sending failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to send verification email")
     *         )
     *     )
     * )
     */
    public function sendEmail(EntityManagerInterface $entityManager, MailerInterface $mailer, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $email = $this->globalService->validate(strtolower($data['email'] ?? ""));
            dd($email);
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
                                        <a href="https://fittrackapi-fmwr.onrender.com" style="background-color: #4caf50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
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
            return $this->json(['type' => 'error', 'message' => 'An error has occurred with the email'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/checkCode', name: 'api_checkCode', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/users/checkCode",
     *     summary="Verify activation code",
     *     description="Validates user's verification code to activate account",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"verificationCode"},
     *             @OA\Property(
     *                 property="verificationCode",
     *                 type="string",
     *                 pattern="^\d{6}$",
     *                 example="123456"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account activated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Account activated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid code format",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid code format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid verification code",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid verification code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Account already active",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="warning"),
     *             @OA\Property(property="message", type="string", example="Account is already active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Could not verify code")
     *         )
     *     )
     * )
     */
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