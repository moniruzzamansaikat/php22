<?php

namespace Php22\Controllers;

use Php22\Db\Database;
use Php22\Http\Request;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        Database::table('users')->insert([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        flash()->set('success', 'User added successfully!');
        $this->redirect('/users');
    }
}
