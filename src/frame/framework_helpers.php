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
