<?php

namespace Php22\Controllers;

class UserController extends BaseController
{
    /**
     * Show the list of users.
     */
    public function index()
    {
        // Sample user data
        $users = [
            ['name' => 'Alice'],
            ['name' => 'Bob'],
            ['name' => 'Charlie']
        ];

        // Render the view with user data
        $this->render('users/index', [
            'users' => $users,
            'appName' => 'User Management'
        ]);
    }

    /**
     * Show a form to create a new user.
     */
    public function create()
    {
        $this->render('users/create');
    }

    /**
     * Handle the form submission for creating a user.
     */
    public function store()
    {
        // Example: Handle form data (you’d process $_POST here)
        $this->redirect('/users');
    }
}
