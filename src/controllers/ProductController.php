<?php

namespace Php22\Controllers;

class ProductController extends BaseController
{
    public function index()
    {
        $this->json([
            "test" => "ok"
        ]);
    }
}