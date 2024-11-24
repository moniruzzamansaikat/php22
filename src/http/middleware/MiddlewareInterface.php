<?php

namespace Php22\http\Middleware;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request.
     *
     * @param mixed $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, $next);
}
