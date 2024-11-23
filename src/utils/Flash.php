<?php

namespace Php22\Utils;

class Flash
{
    /**
     * Set a flash message.
     *
     * @param string $key
     * @param string $message
     * @return void
     */
    public static function set(string $key, string $message)
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Get a flash message.
     *
     * @param string $key
     * @return string|null
     */
    public static function get(string $key): ?string
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]); // Clear the message after retrieving
            return $message;
        }

        return null;
    }
}
