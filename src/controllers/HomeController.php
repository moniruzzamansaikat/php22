<?php

namespace Php22\Controllers;

class HomeController extends BaseController
{
    public function index()
    {
        $title = 'Test';
        
        $this->render('index', compact('title'));
    }
}
