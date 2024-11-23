<?php

namespace Php22\Controllers;

use Php22\Db\Database;

class UserController extends BaseController
{
    public function index()
    {
        $users = Database::table('users')
            ->select(['id', 'username'])
            ->get();

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
