<?php

namespace Php22\Controllers;

use Php22\Db\Database;
use Php22\Http\Request;
use Php22\Utils\Flash;
use Php22\Utils\Validator;

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

    public function store(Request $request, Validator $validator)
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        $data = $request->all();

        $validator->validate($rules, $data);

        if (!$validator->passes()) {
            foreach ($validator->errors() as $field => $error) {
                Flash::set($field, $error);
            }
            $this->redirect('/users');
            return;
        }

        Database::table('users')->insert([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        Flash::set('success', 'User added successfully!');
        $this->redirect('/users');
    }
}
