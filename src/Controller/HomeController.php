<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $info = [
            'message' => 'Welcome to our FitTrackAPI!👋',
            'info' => [
                '/api/users' => [
                    '/seeAllUsers' => 'You will be able to see all the users of the database, function for administrators',
                    '/seeOneUser/{id}' => 'You will be able to see the selected user by id, function for administrators and users',
                    '/signUp' => 'You can register in the app',
                    '/signIn' => 'You will be able to login to the app by email or username',
                    '/deleteUser/{id}' => 'You will be able to delete/deactivate a user through the user id, function for administrators',
                    '/modifyUser/{id}' => 'You will be able to modify a user through the id, function for administrators and users'
                ],
                '/api/exercises' => [
                    '/seeAllExercises' => 'You will be able to see all exercises in the database',
                    '/seeAllActiveExercises' => 'You will be able to see only active exercises',
                    '/seeOneExercise/{id}' => 'You will be able to see the selected exercise by id, function for administrators and users',
                    '/addExercise' => 'You can add a new exercise to the database, function for administrators and coaches',
                    '/deleteExercise/{id}' => 'You will be able to delete/deactivate an exercise through the id, function for administrators and coaches',
                    '/modifyExercise/{id}' => 'You will be able to modify an exercise through the id, function for administrators and coaches'
                ]
            ]
        ];

        return $this->render('home/infoAPI.html.twig', [
            'apiInfo' => $info,
            'jsonInfo' => json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ]);
    }
}
