<?php
/**
 * Author: Jon Garcia.
 * Date: 2/18/17
 * Time: 1:42 PM
 */

namespace App\Core\Interfaces;


interface Authenticatable
{
    public function isLoggedIn();

    public function login();

    public function logout();

    public function tryLoginWithCookie();

    public function retrieveById();

    public function retrieveByToken();

    public function deleteLoginCookie();

}