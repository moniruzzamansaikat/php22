<?php

namespace Php22\Utils;

class Flash
{
    /**
     * Set a flash message.
     *
     * @param string $key
     * @param mixed $message
     * @return void
     */
    public function set(string $key, mixed $message)
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
     * @return mixed
     */
    public function get(string $key): mixed
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
