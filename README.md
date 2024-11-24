# php22

To install: `composer require monirsaikat/php22:dev-main`

# Request

The `Request` class provides a set of methods to interact with the HTTP request data, including inputs, query parameters, headers, cookies, files, and more.

## Methods

### `isPost(): bool`

- **Description**: Checks if the current request is a POST request.
- **Returns**: `true` if the request method is POST, `false` otherwise.

### `isGet(): bool`

- **Description**: Checks if the current request is a GET request.
- **Returns**: `true` if the request method is GET, `false` otherwise.

### `input(string $key, $default = null)`

- **Description**: Retrieves a specific input value from the POST data.
- **Parameters**:
  - `$key` (string): The key to retrieve from the input.
  - `$default` (mixed): The default value if the key doesn't exist.
- **Returns**: The value associated with the key, or the default value.

### `all(): array`

- **Description**: Retrieves all input data from the POST request.
- **Returns**: An associative array of all POST data.

### `query(string $key, $default = null)`

- **Description**: Retrieves a specific query parameter from the GET data.
- **Parameters**:
  - `$key` (string): The key to retrieve from the query parameters.
  - `$default` (mixed): The default value if the key doesn't exist.
- **Returns**: The value associated with the key, or the default value.

### `allQuery(): array`

- **Description**: Retrieves all query parameters from the GET request.
- **Returns**: An associative array of all GET data.

### `header(string $key, $default = null)`

- **Description**: Retrieves a specific header value.
- **Parameters**:
  - `$key` (string): The header name.
  - `$default` (mixed): The default value if the header doesn't exist.
- **Returns**: The value of the specified header, or the default value.

### `headers(): array`

- **Description**: Retrieves all headers from the current request.
- **Returns**: An associative array of all headers.

### `cookie(string $key, $default = null)`

- **Description**: Retrieves a specific cookie value.
- **Parameters**:
  - `$key` (string): The cookie name.
  - `$default` (mixed): The default value if the cookie doesn't exist.
- **Returns**: The value of the specified cookie, or the default value.

### `allCookies(): array`

- **Description**: Retrieves all cookies from the current request.
- **Returns**: An associative array of all cookies.

### `file(string $key): ?array`

- **Description**: Retrieves a specific uploaded file.
- **Parameters**:
  - `$key` (string): The file input name.
- **Returns**: An array containing file information, or `null` if not found.

### `allFiles(): array`

- **Description**: Retrieves all uploaded files from the current request.
- **Returns**: An associative array of all uploaded files.

### `method(): string`

- **Description**: Gets the HTTP request method.
- **Returns**: The request method as a string (e.g., 'GET', 'POST').

### `uri(): string`

- **Description**: Gets the full request URI.
- **Returns**: The request URI as a string.

### `path(): string`

- **Description**: Gets the request path without the query string.
- **Returns**: The request path as a string.

### `ip(): ?string`

- **Description**: Gets the client's IP address.
- **Returns**: The IP address as a string, or `null` if not available.

### `isAjax(): bool`

- **Description**: Checks if the request is an AJAX request.
- **Returns**: `true` if the request is an AJAX request, `false` otherwise.

### `isSecure(): bool`

- **Description**: Checks if the request is made over HTTPS.
- **Returns**: `true` if the request is secure, `false` otherwise.

### `json(bool $assoc = true)`

- **Description**: Retrieves JSON input data from the request body.
- **Parameters**:
  - `$assoc` (bool): Whether to return the data as an associative array (`true`) or an object (`false`).
- **Returns**: The decoded JSON data.

### `validate(array $rules): array`

- **Description**: Validates the input data using the given rules.
- **Parameters**:
  - `$rules` (array): An associative array of validation rules (e.g., `['username' => 'required']`).
- **Returns**: The validated data.
- **Note**: If validation fails, errors are flashed, and the user is redirected.

---
