<?php
/**
 * Created by Jon Garcia
 */
namespace App\Core\Http;

use App\Controllers\UsersController;
use App\Core\Http\Response;
use App\Core\Validator;

/**
 * Class Controller
 * @package App\Core\Http
 */
class Controller {

    use Validator;

    /**
     * try to login with cookie
     */
    function __construct()
    {
        if (!$this->isLoggedIn() && isset($_COOKIE['login_cookie'])) {
            if ($this instanceof usersController) {
                $this->tryLoginWithCookie();
            }
            else {
                $user = new usersController();
                $user->tryLoginWithCookie();
            }
        }
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        if (isset($_SESSION['user_logged_in'])) {
            return true;
        }
        return false;
    }

    /**
     * @param $location
     */
    protected function redirect($location)
    {
        if ($location === 'home') {
            $location = '/';
        }
        Response::setHeader('location', $location);
        Response::render('', 308);
    }

    /**
     * @param $dir
     * @return array
     */
    public static function getDirectoryFiles($dir)
    {
        $files = null;

        $relPath = str_replace(PUBLIC_PATH, '', $dir);

        if (file_exists($dir)) {
            foreach (scandir( $dir ) as $file) {
                if ($file !== '.' && $file !== '..') {
                    $files[] = $relPath . '/' . $file;
                }
            }
        }

        return $files;
    }
}