<?php

namespace Php22\http\Middleware;

use Php22\Http\Request;
use Php22\Utils\Session;

class CsrfMiddleware
{
    public function handle()
    {
        if (request()->isPost()) {
            $csrfToken = Session::get('_csrf_token');
            $submittedToken = request()->input('_token');

            if (!$csrfToken || $csrfToken !== $submittedToken) {
                http_response_code(419);
                echo 'Invalid csrf token';
                exit();
            }
        }
    }
}
