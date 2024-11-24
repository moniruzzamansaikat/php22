<?php

use Php22\Db\Database;
use Php22\Utils\Flash;

/**
 * Helper to create a Flash instance.
 *
 * @return Flash
 */
function flash()
{
    return new Flash();
}

/**
 * Helper to create a Database instance.
 *
 * @return Database
 */
function db()
{
    return new Database();
}

function hash_password(string $password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Resolve the base path dynamically based on the entry point.
 *
 * @param string $path Optional relative path to append.
 * @return string
 */
function base_path(string $path = ''): string
{
    $basePath = realpath($_SERVER['DOCUMENT_ROOT'] . '/../');
    return $path ? $basePath . DIRECTORY_SEPARATOR . $path : $basePath;
}


function debug(...$vars): void
{
    echo '<style>
        pre.debug-output {
            background: #282c34;
            color: #61dafb;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
            font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
            line-height: 1.5;
            overflow-x: auto;
        }
    </style>';

    echo '<pre class="debug-output">';
    foreach ($vars as $var) {
        // Use print_r for better structure and formatting
        echo htmlspecialchars(print_r($var, true));
    }
    echo '</pre>';
    exit(1); // Terminate script execution
}
