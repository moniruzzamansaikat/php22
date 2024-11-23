<?php

namespace Php22\Http;

class Request
{
    /**
     * Check if the current request is a POST request.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Check if the current request is a GET request.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Retrieve a specific input value.
     *
     * @param string $key The key to retrieve from the input.
     * @param mixed $default The default value if the key doesn't exist.
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Retrieve all input data.
     *
     * @return array
     */
    public function all(): array
    {
        return $_POST;
    }

    /**
     * Retrieve a specific query parameter.
     *
     * @param string $key The key to retrieve from the query parameters.
     * @param mixed $default The default value if the key doesn't exist.
     * @return mixed
     */
    public function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Retrieve all query parameters.
     *
     * @return array
     */
    public function allQuery(): array
    {
        return $_GET;
    }

    /**
     * Retrieve a header value.
     *
     * @param string $key The header key.
     * @param mixed $default The default value if the header doesn't exist.
     * @return mixed
     */
    public function header(string $key, $default = null)
    {
        $headers = getallheaders();
        return $headers[$key] ?? $default;
    }
}
