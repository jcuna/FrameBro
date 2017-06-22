<?php

/**
 *
 * Creator Jon Garcia
 *
 * Session class
 *
 * handles the session stuff. creates session when no one exists, sets and
 * gets values, and closes the session properly (=logout). Those methods
 * are STATIC, which means you can call them with Session::get(XXX);
 */

namespace App\Core\Http;

/**
 * Class Session
 * @package App\Core
 */
class Session
{
    /**
     * starts the session
     */
    public function __construct()
    {
        // if no session exist, start the session
        if (session_id() == '') {
            session_name('framebro');
            session_start();
        }
    }

    /**
     * sets a specific value to a specific key of the session
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * gets/returns the value of a specific key of the session
     * @param mixed $key Usually a string, right ?
     * @return mixed
     */
    public function get($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return null;
    }

    /**
     * deletes the session (= logs the user out)
     */
    public function destroy()
    {
        session_destroy();
    }
}
