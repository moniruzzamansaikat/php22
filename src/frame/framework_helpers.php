<?php

use Php22\Db\Database;
use Php22\Utils\Flash;

/**
 * Helper to create a Flash instance.
 *
 * @return Flash
 */
function flash() {
    return new Flash();
}

/**
 * Helper to create a Database instance.
 *
 * @return Database
 */
function db() {
    return new Database();
}

function hash_password(string $password) {
    return password_hash($password, PASSWORD_DEFAULT);
}