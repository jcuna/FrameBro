<?php
/**
 * Created By: Jon Garcia
 * Date: 1/16/16
 */

namespace App\Models;

use App\Core\Request;
use App\Core\Model\Loupe;
use App\Core\Http\Session;
use App\Core\Http\View;
use App;

class User extends Loupe
{

    protected $primaryKey = 'uid';

    /**
     * @param Request $params
     * @return bool
     */
    public function Authenticate(Request $params)
    {
        if (password_verify($params->password, $this->password)) {
            $session = App::getRequest()->session;
            $session->set('user_logged_in', true);
            unset($this->password);

            foreach ($this as $property => $attribute) {
                $session->set($property, $attribute);
                if ($property !== 'uid') {
                    unset($this->{$property});
                }
            }

            //The following will generate a token and a cookie for the logged in user to be remembered
            // generate 64 char random string
            $random_token_string = hash('sha256', mt_rand());

            // write that token into database
            $this->login_token = $random_token_string;

            if ($this->save()) {

                // generate cookie string that consists of user id, random string and combined hash of both
                $cookie_string_first_part = $this->uid . ':' . $random_token_string;
                $cookie_string_hash = hash('sha256', $cookie_string_first_part);
                $cookie_string = $cookie_string_first_part . ':' . $cookie_string_hash;

                // set cookie
                setcookie('login_cookie', $cookie_string, time() + (86400 * 14), "/");
                return true;
            }
        }
        return false;
    }

    /**
     * @return $this
     */
    public function roles()
    {
        return $this->belongsToMany('\\App\Models\\Role');
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function loginWithCookie()
    {
        // do we have a cookie var ?
        if (!Request::hasCookie("login_cookie")) {
            View::error('There was a problem logging you in automatically');
            return false;
        }

        $cookie = Request::getCookie("login_cookie");
        // check cookie's contents, check if cookie contents belong together
        list($user_id, $token, $hash) = explode(':', $cookie);
        if ($hash !== hash('sha256', $user_id . ':' . $token)) {
            View::error('There was a problem logging you in automatically');
            return false;
        }

        // do not log in when token is empty
        if (empty($token)) {
            View::error('There was a problem logging you in automatically');
            return false;
        }

        $this->with('roles')->where('uid', $user_id)->where('login_token', $token)
            ->groupConcat(['roles.name' => 'roles'])
            ->first(['users.*']);

        if ($this->count === 1) {

            $session = App::getRequest()->session;
            $session->set('user_logged_in', true);
            foreach ($this as $property => $attribute) {
                $session->set($property, $attribute);
                if ($property !== 'uid') {
                    unset($this->{$property});
                }
            }

            //The following will generate a token and a cookie for the logged in user to be remembered
            // generate 64 char random string
            $random_token_string = hash('sha256', mt_rand());

            // write that token into database
            $this->login_token = $random_token_string;

            if ($this->save()) {

                // generate cookie string that consists of user id, random string and combined hash of both
                $cookie_string_first_part = $this->uid . ':' . $random_token_string;
                $cookie_string_hash = hash('sha256', $cookie_string_first_part);
                $cookie_string = $cookie_string_first_part . ':' . $cookie_string_hash;

                // set cookie
                setcookie('login_cookie', $cookie_string, time() + (86400 * 14), "/");
                return true;
            }
        }
        return false;
    }

    /**
     *
     */
    public function deleteLoginCookie()
    {
        setcookie('login_cookie', false, time() - (3600 * 3650), '/');
    }

    /**
     * @return bool
     */
    public function logout()
    {
        setcookie('login_cookie', false, time() - (3600 * 3650), '/');
        $session = App::getRequest()->session;
        $session->destroy();
        return true;
    }
}