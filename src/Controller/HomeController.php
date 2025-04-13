<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $info = [
            'message' => 'Welcome to our FitTrackAPI!👋',
            'info' => [
                '/api/info' => [
                    '/json' => [
                        'description' => 'Returns a json with the api usage information',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/web' => [
                        'description' => 'Returns a twig template with the api usage information',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ]
                ],
                '/api/users' => [
                    '/seeAllUsers' => [
                        'description' => 'You will be able to see all the users of the database, function for administrators',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/seeOneUser/{id}' => [
                        'description' => 'You will be able to see the selected user by id, function for administrators and users',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/signUp' => [
                        'description' => 'You can register in the app',
                        'metadata' =>  [
                            'email' => 'Valid email format',
                            'username' => 'Valid username format (Only lowercase letters and numbers)',
                            'password' => 'Valid password format (Capital letter / Lowercase letter / 0ne number / Symbol or special character / At least 5 characters in length)',
                            'repeatPassword' => 'The same as the password'
                        ],
                        'method' => 'POST'
                    ],
                    '/signIn' => [
                        'description' => 'You will be able to login to the app by email or username',
                        'metadata' =>  [
                            'email' => 'Valid email format',
                            'password' => 'Valid password format (Capital letter / Lowercase letter / 0ne number / Symbol or special character / At least 5 characters in length)',
                            'rememberme' => 'Boolean'
                        ],
                        'method' => 'POST'
                    ],
                    '/signOut' => [
                        'description' => 'You will be able to log off',
                        'metadata' => 'No parameters needed',
                        'method' => 'POST'
                    ],
                    '/deleteUser/{id}' => [
                        'description' => 'You will be able to delete/deactivate a user through the user id, function for administrators',
                        'metadata' => 'No parameters needed',
                        'method' => 'DELETE / POST'
                    ],
                    '/modifyUser/{id}' => [
                        'description' => 'You will be able to modify a user through the id, function for administrators and users',
                        'metadata' => [
                            'username' => 'Valid username format (Only lowercase letters and numbers)',
                            'password' => 'Valid password format (Capital letter / Lowercase letter / 0ne number / Symbol or special character / At least 5 characters in length)',
                            'role' => 'ROLE_ADMIN / ROLE_USER / ROLE_COACH',
                            'method' => 'PUT / POST / GET'
                        ]
                    ],
                    '/tokenExisting' => [
                        'description' => 'Check if the user has clicked on remember me',
                        'metadata' => 'No parameters needed',
                        'method' => 'POST'
                    ],
                    '/whoami' => [
                        'description' => 'You will be able to know who you are (the id)',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ]
                ],
                '/api/exercises' => [
                    '/seeAllExercises' => [
                        'description' => 'You will be able to see all exercises in the database',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/seeAllActiveExercises' => [
                        'description' => 'You will be able to see only active exercises',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/seeOneExercise/{id}' => [
                        'description' => 'You will be able to see the selected exercise by id, function for administrators and users',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/addExercise' => [
                        'description' => 'You can add a new exercise to the database, function for administrators and coaches',
                        'metadata' => [
                            'name' => 'Valid name format (max 30 characters)',
                            'description' => 'Valid description format (max 500 characters min 10 characters)',
                            'category' => 'CHEST / SHOULDER / TRICEPS / BACK / BICEPS / ABDOMINALS / FEMORAL / QUADRICEPS / CALVES',
                        ],
                        'method' => 'POST'
                    ],
                    '/deleteExercise/{id}' => [
                        'description' => 'You will be able to delete/deactivate an exercise through the id, function for administrators and coaches',
                        'metadata' => 'No parameters needed',
                        'method' => 'DELETE / POST'
                    ],
                    '/modifyExercise/{id}' => [
                        'description' => 'You will be able to modify an exercise through the id, function for administrators and coaches',
                        'metadata' => [
                            'name' => 'Valid name format (max 30 characters)',
                            'description' => 'Valid description format (max 500 characters min 10 characters)',
                            'category' => 'CHEST / SHOULDER / TRICEPS / BACK / BICEPS / ABDOMINALS / FEMORAL / QUADRICEPS / CALVES',
                        ],
                        'method' => 'PUT / POST / GET'
                    ]
                ]
            ]
        ];

        return $this->render('home/infoAPI.html.twig', [
            'apiInfo' => $info,
            'jsonInfo' => json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ]);
    }

    #[Route('/api/info/web', name: 'app_web')]
    public function web(): Response
    {
        $info = [
            'message' => 'Welcome to our FitTrackAPI!👋',
            'info' => [
                '/api/info' => [
                    '/json' => [
                        'description' => 'Returns a json with the api usage information',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/web' => [
                        'description' => 'Returns a twig template with the api usage information',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ]
                ],
                '/api/users' => [
                    '/seeAllUsers' => [
                        'description' => 'You will be able to see all the users of the database, function for administrators',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/seeOneUser/{id}' => [
                        'description' => 'You will be able to see the selected user by id, function for administrators and users',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/signUp' => [
                        'description' => 'You can register in the app',
                        'metadata' =>  [
                            'email' => 'Valid email format',
                            'username' => 'Valid username format (Only lowercase letters and numbers)',
                            'password' => 'Valid password format (Capital letter / Lowercase letter / 0ne number / Symbol or special character / At least 5 characters in length)',
                            'repeatPassword' => 'The same as the password'
                        ],
                        'method' => 'POST'
                    ],
                    '/signIn' => [
                        'description' => 'You will be able to login to the app by email or username',
                        'metadata' =>  [
                            'email' => 'Valid email format',
                            'password' => 'Valid password format (Capital letter / Lowercase letter / 0ne number / Symbol or special character / At least 5 characters in length)',
                            'rememberme' => 'Boolean'
                        ],
                        'method' => 'POST'
                    ],
                    '/signOut' => [
                        'description' => 'You will be able to log off',
                        'metadata' => 'No parameters needed',
                        'method' => 'POST'
                    ],
                    '/deleteUser/{id}' => [
                        'description' => 'You will be able to delete/deactivate a user through the user id, function for administrators',
                        'metadata' => 'No parameters needed',
                        'method' => 'DELETE / POST'
                    ],
                    '/modifyUser/{id}' => [
                        'description' => 'You will be able to modify a user through the id, function for administrators and users',
                        'metadata' => [
                            'username' => 'Valid username format (Only lowercase letters and numbers)',
                            'password' => 'Valid password format (Capital letter / Lowercase letter / 0ne number / Symbol or special character / At least 5 characters in length)',
                            'role' => 'ROLE_ADMIN / ROLE_USER / ROLE_COACH',
                            'method' => 'PUT / POST / GET'
                        ]
                    ],
                    '/tokenExisting' => [
                        'description' => 'Check if the user has clicked on remember me',
                        'metadata' => 'No parameters needed',
                        'method' => 'POST'
                    ],
                    '/whoami' => [
                        'description' => 'You will be able to know who you are (the id)',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ]
                ],
                '/api/exercises' => [
                    '/seeAllExercises' => [
                        'description' => 'You will be able to see all exercises in the database',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/seeAllActiveExercises' => [
                        'description' => 'You will be able to see only active exercises',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/seeOneExercise/{id}' => [
                        'description' => 'You will be able to see the selected exercise by id, function for administrators and users',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/addExercise' => [
                        'description' => 'You can add a new exercise to the database, function for administrators and coaches',
                        'metadata' => [
                            'name' => 'Valid name format (max 30 characters)',
                            'description' => 'Valid description format (max 500 characters min 10 characters)',
                            'category' => 'CHEST / SHOULDER / TRICEPS / BACK / BICEPS / ABDOMINALS / FEMORAL / QUADRICEPS / CALVES',
                        ],
                        'method' => 'POST'
                    ],
                    '/deleteExercise/{id}' => [
                        'description' => 'You will be able to delete/deactivate an exercise through the id, function for administrators and coaches',
                        'metadata' => 'No parameters needed',
                        'method' => 'DELETE / POST'
                    ],
                    '/modifyExercise/{id}' => [
                        'description' => 'You will be able to modify an exercise through the id, function for administrators and coaches',
                        'metadata' => [
                            'name' => 'Valid name format (max 30 characters)',
                            'description' => 'Valid description format (max 500 characters min 10 characters)',
                            'category' => 'CHEST / SHOULDER / TRICEPS / BACK / BICEPS / ABDOMINALS / FEMORAL / QUADRICEPS / CALVES',
                        ],
                        'method' => 'PUT / POST / GET'
                    ]
                ]
            ]
        ];

        return $this->render('home/infoAPI.html.twig', [
            'apiInfo' => $info,
            'jsonInfo' => json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ]);
    }

    #[Route('/api/info/json', name: 'app_json')]
    public function jjson(): JsonResponse
    {
        $info = [
            'message' => 'Welcome to our FitTrackAPI!👋',
            'info' => [
                '/api/info' => [
                    '/json' => [
                        'description' => 'Returns a json with the api usage information',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/web' => [
                        'description' => 'Returns a twig template with the api usage information',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ]
                ],
                '/api/users' => [
                    '/seeAllUsers' => [
                        'description' => 'You will be able to see all the users of the database, function for administrators',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/seeOneUser/{id}' => [
                        'description' => 'You will be able to see the selected user by id, function for administrators and users',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/signUp' => [
                        'description' => 'You can register in the app',
                        'metadata' =>  [
                            'email' => 'Valid email format',
                            'username' => 'Valid username format (Only lowercase letters and numbers)',
                            'password' => 'Valid password format (Capital letter / Lowercase letter / 0ne number / Symbol or special character / At least 5 characters in length)',
                            'repeatPassword' => 'The same as the password'
                        ],
                        'method' => 'POST'
                    ],
                    '/signIn' => [
                        'description' => 'You will be able to login to the app by email or username',
                        'metadata' =>  [
                            'email' => 'Valid email format',
                            'password' => 'Valid password format (Capital letter / Lowercase letter / 0ne number / Symbol or special character / At least 5 characters in length)',
                            'rememberme' => 'Boolean'
                        ],
                        'method' => 'POST'
                    ],
                    '/signOut' => [
                        'description' => 'You will be able to log off',
                        'metadata' => 'No parameters needed',
                        'method' => 'POST'
                    ],
                    '/deleteUser/{id}' => [
                        'description' => 'You will be able to delete/deactivate a user through the user id, function for administrators',
                        'metadata' => 'No parameters needed',
                        'method' => 'DELETE / POST'
                    ],
                    '/modifyUser/{id}' => [
                        'description' => 'You will be able to modify a user through the id, function for administrators and users',
                        'metadata' => [
                            'username' => 'Valid username format (Only lowercase letters and numbers)',
                            'password' => 'Valid password format (Capital letter / Lowercase letter / 0ne number / Symbol or special character / At least 5 characters in length)',
                            'role' => 'ROLE_ADMIN / ROLE_USER / ROLE_COACH',
                            'method' => 'PUT / POST / GET'
                        ]
                    ],
                    '/tokenExisting' => [
                        'description' => 'Check if the user has clicked on remember me',
                        'metadata' => 'No parameters needed',
                        'method' => 'POST'
                    ],
                    '/whoami' => [
                        'description' => 'You will be able to know who you are (the id)',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ]
                ],
                '/api/exercises' => [
                    '/seeAllExercises' => [
                        'description' => 'You will be able to see all exercises in the database',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/seeAllActiveExercises' => [
                        'description' => 'You will be able to see only active exercises',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/seeOneExercise/{id}' => [
                        'description' => 'You will be able to see the selected exercise by id, function for administrators and users',
                        'metadata' => 'No parameters needed',
                        'method' => 'GET'
                    ],
                    '/addExercise' => [
                        'description' => 'You can add a new exercise to the database, function for administrators and coaches',
                        'metadata' => [
                            'name' => 'Valid name format (max 30 characters)',
                            'description' => 'Valid description format (max 500 characters min 10 characters)',
                            'category' => 'CHEST / SHOULDER / TRICEPS / BACK / BICEPS / ABDOMINALS / FEMORAL / QUADRICEPS / CALVES',
                        ],
                        'method' => 'POST'
                    ],
                    '/deleteExercise/{id}' => [
                        'description' => 'You will be able to delete/deactivate an exercise through the id, function for administrators and coaches',
                        'metadata' => 'No parameters needed',
                        'method' => 'DELETE / POST'
                    ],
                    '/modifyExercise/{id}' => [
                        'description' => 'You will be able to modify an exercise through the id, function for administrators and coaches',
                        'metadata' => [
                            'name' => 'Valid name format (max 30 characters)',
                            'description' => 'Valid description format (max 500 characters min 10 characters)',
                            'category' => 'CHEST / SHOULDER / TRICEPS / BACK / BICEPS / ABDOMINALS / FEMORAL / QUADRICEPS / CALVES',
                        ],
                        'method' => 'PUT / POST / GET'
                    ]
                ]
            ]
        ];

        return $this->json($info, Response::HTTP_OK);
    }
}
