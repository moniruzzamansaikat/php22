<?php

namespace Php22\Http;

use Php22\Utils\Validator;

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

    /**
     * Retrieve all headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return getallheaders();
    }

    /**
     * Retrieve a specific cookie value.
     *
     * @param string $key The cookie name.
     * @param mixed $default The default value if the cookie doesn't exist.
     * @return mixed
     */
    public function cookie(string $key, $default = null)
    {
        return $_COOKIE[$key] ?? $default;
    }

    /**
     * Retrieve all cookies.
     *
     * @return array
     */
    public function allCookies(): array
    {
        return $_COOKIE;
    }

    /**
     * Retrieve a file from the uploaded files.
     *
     * @param string $key The file input name.
     * @return array|null
     */
    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Retrieve all uploaded files.
     *
     * @return array
     */
    public function allFiles(): array
    {
        return $_FILES;
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get the full request URI.
     *
     * @return string
     */
    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Get the request path without query string.
     *
     * @return string
     */
    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return $path ?? '/';
    }

    /**
     * Get the client's IP address.
     *
     * @return string|null
     */
    public function ip(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Check if the request is an AJAX request.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if the request is over HTTPS.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443;
    }

    /**
     * Retrieve JSON input data as an associative array or object.
     *
     * @param bool $assoc Whether to return an associative array.
     * @return mixed
     */
    public function json(bool $assoc = true)
    {
        $input = file_get_contents('php://input');
        return json_decode($input, $assoc);
    }

    /**
     * Validate the input data using the given rules.
     *
     * @param array $rules Validation rules (e.g., ['username' => 'required']).
     * @return array The validated data.
     */
    public function validate(array $rules): array
    {
        $validator = new Validator();
        
        $data = $this->all(); 
        $validator->validate($rules, $data);
        
        if (!$validator->passes()) {
            $validator->flashErrors();
            header('Location: /users'); 
            exit();
        }

        return $data; 
    }
}
