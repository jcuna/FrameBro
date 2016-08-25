<?php
/**
 * Created by Jon Garcia
 */
namespace App\Core\Http;

use App\Controllers\usersController;
use App\Core\Exceptions\ControllerException;
use App\Core\Html\Validator;
use App\Core\Response;
use App\Core\View;

/**
 * Class Controller
 * @package App\Core\Http
 */
class Controller {

    use Validator;

    /**
     * Built in before filters.
     *
     * @var array
     */
    protected $beforeFilter = [
        'authenticated' => [],
        'hasRole'       => null
    ];

    /**
     * Add custom method to be run as a before filter.
     *
     * @var array [ 'class' => '\\namespace\\className', 'method' => 'methodName' ]
     */
    protected $beforeFilterCustom = [
        'class'     => null,
        'method'    => null
    ];

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
     * @param $name
     * @param $arguments
     * @return mixed|string|void
     * @throws ControllerException
     */
    public function __call($name, $arguments)
    {
        $name = str_replace('{action}', '', $name);

        // if there's anything in the before filter return that.
        if ($beforeFilter = $this->runBeforeFilters($name)) {
            return $beforeFilter;
        }

        //If there's anything inside the beforeFilterCustom Method, and it's not a boolean, we return that.
        if ($beforeFilterCustomMethod = $this->runBeforeFilterCustomMethod($name, $arguments)) {
            if (!is_bool($beforeFilterCustomMethod)) {
                return $beforeFilterCustomMethod;
            }
        }

        return call_user_func_array( array($this, $name) , $arguments );
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed|void
     * @throws ControllerException
     */
    private function runBeforeFilterCustomMethod($name,  $arguments )
    {
        if (isset($this->beforeFilterCustom['class']) && isset($this->beforeFilterCustom['method']) ) {

            if (is_null($this->beforeFilterCustom['class']) || is_null($this->beforeFilterCustom['method'])) {
                return false;
            }

            $class = $this->beforeFilterCustom['class'];
            $object = new $class();
            $method = $this->beforeFilterCustom['method'];

            if (method_exists($object, $method)) {

                return call_user_func_array(array($object, $method), $arguments);

            } else {

                throw new ControllerException("Custom before filter method failed.
                $method in $class, doesn't exist. Calling $name");
            }
        }

        return false;
    }

    /**
     * @param $name
     * @return string
     * @throws ControllerException
     */
    private function runBeforeFilters($name)
    {
        if (is_array($this->beforeFilter['authenticated'])) {
            if (in_array($name, $this->beforeFilter['authenticated']) && !$this->isLoggedIn()
                || in_array('all', $this->beforeFilter['authenticated']) && !$this->isLoggedIn()) {
                
                return View::render('errors/error', 'Access denied', 403);
            }
        } else {
                throw new ControllerException("authenticated must be an array. " .
                    gettype($this->beforeFilter['authenticated']) . " given");
            }

        if ( isset($this->beforeFilter['hasRole']) && !is_null($this->beforeFilter['hasRole'])
            && !View::hasRole($this->beforeFilter['hasRole'])) {

            return View::render('errors/error', 'Access denied', 403);
        }
    }

    /**
     * @return bool
     */
    protected function isLoggedIn()
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