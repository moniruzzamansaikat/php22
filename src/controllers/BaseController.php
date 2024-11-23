<?php

namespace Php22\Controllers;

use Php22\TemplateEngine;

abstract class BaseController
{
    protected $templateEngine;

    public function __construct()
    {
        $this->templateEngine = new TemplateEngine(
            __DIR__ . '/../views',  // Path to views folder
            __DIR__ . '/../cache'  // Path to cache folder
        );
    }

    /**
     * Render a view file with optional data.
     *
     * @param string $view The view file name (without extension).
     * @param array $data Data to pass to the view.
     * @return void
     */
    protected function render(string $view, array $data = [])
    {
        echo $this->templateEngine->render($view, $data);
    }

    /**
     * Redirect to a given URL.
     *
     * @param string $url The URL to redirect to.
     * @return void
     */
    protected function redirect(string $url)
    {
        header("Location: $url");
        exit;
    }
}
