<?php

namespace Php22\Controllers;

class UserController extends BaseController
{
    public function index()
    {
        $users = [
            ['name' => 'Alice'],
            ['name' => 'Bob'],
            ['name' => 'Charlie']
        ];

        $this->render('users/index', [
            'users' => $users,
            'appName' => 'User Management'
        ]);
    }

    public function create()
    {
        $this->render('users/create');
    }

    public function store()
    {
        $this->redirect('/users');
    }
}
