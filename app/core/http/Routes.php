<?php
/**
 * Author: Jon Garcia
 * Date: 2/5/16
 * Time: 9:25 AM
 */

namespace App\Core\Http;

use App\Core\Exceptions\AppException;
use App\Core\Interpreter;

/**
 * Class Routes
 * @package App\Core\Http
 */
class Routes
{
    /**
     * All the routes registered routes
     *
     * @var array
     */
    private static $routes = [];

    /**
     * If declared in the routes, contains the action to be taken when a page is missing.
     * @var callable | string
     */
    private static $missing;

    /**
     * Valid request methods
     * 
     * @var array
     */
    private static $validRequestMethods = [
        "DELETE",
        "GET",
        "PATCH",
        "POST",
        "PUT",
        "OPTIONS",
        "HEAD"
    ];

    /**
     * The requested uri
     *
     * @var string
     */
    public $uri;

    /**
     * Array of requested uri
     *
     * @var array
     */
    public $arUri = [];

    /**
     * Parts of the uri that are arguments
     *
     * @var array
     */
    public $arguments = [];

    /**
     * Static copy of @$arguments.
     *
     * @var array
     */
    public static $args = [];

    /**
     * The registered controller
     *
     * @var
     */
    public $controller;

    /**
     * The register action
     *
     * @var string
     */
    public $action = 'index';

    /**
     * Reference to itself
     *
     * @var Routes
     */
    public static $instance;

    /**
     * Routes constructor.
     */
    public function __construct()
    {
        // parses url
        $this->arUri = $this->parseURL();

        /**
         * This route is part of the ajax framework.
         **/
        self::post('AjaxController', 'App\\Core\\Ajax\\AjaxController@jsonResponder');

        // Includes the router file.
        $this->includeRouter();

        self::$instance = $this;
    }

    /**
     *
     */
    public function callMissingPage()
    {
        if (!empty(self::$missing)) {
            return call_user_func(self::$missing);
        } else {
            return !ddd(['No controller', debug_backtrace()]);
        }
    }

    /**
     * @return bool
     * @throws AppException
     */
    public function validateRoutes()
    {
        $result = false;
        if (file_exists(ABSOLUTE_PATH . '/app/controllers/' . $this->controller . 'Controller.php')) {
            $this->controller = "\\App\\Controllers\\" . $this->controller . 'Controller';
        }
        elseif (file_exists(ABSOLUTE_PATH . '/app/controllers/' . $this->controller . '.php')) {
            $this->controller = "\\App\\Controllers\\" . $this->controller;
        }

        $this->controller = new $this->controller(); //throws exception if not exists.

        if (method_exists($this->controller, $this->action)) {
            $result = true;
        }
        else {
            throw new AppException("Invalid method $this->action");
        }
        return $result;
    }

    /**
     * @param $route
     * @param $endpoint
     * @param $via array
     */
    protected static function get($route, $endpoint, array $via = ['via' => NULL]) {
        self::all($route, $endpoint, $via, 'GET');
    }

    /**
     * @param $route
     * @param $endpoint
     * @param $via array
     */
    protected static function post($route, $endpoint, array $via = ['via' => NULL]) {
        self::all($route, $endpoint, $via, 'POST');
    }

    /**
     * @param $name
     * @param $arguments[0] = string $route
     *        $arguments[1] = string $endpoint
     *        $arguments[0] = array $via
     * @throws AppException
     */
    public static function __callStatic($name, $arguments)
    {
        $method = strtoupper($name);

        if (in_array($method, self::$validRequestMethods)) {
            $via = ['via' => NULL];
            if (isset($arguments[2])) {
                $via = $arguments[2];
            }
            self::all($arguments[0], $arguments[1], $via, $method);
        } else {
            throw new AppException("Invalid method call $name");
        }
    }

    /**
     * @param $route
     * @param $endpoint
     * @param $method
     * @param $via array
     * @throws AppException
     */
    protected static function all($route, $endpoint, array $via = ['via' => NULL], $method = 'ALL') {

        if (is_callable($endpoint)) {
            self::$routes[] = [
                'route' => $route,
                'controller' => 'callable',
                'action' => $endpoint,
                'method' => $method,
                'via' => $via['via']
            ];
        }
        else {
            $actionController = explode('@', $endpoint);
            if (!isset($actionController[0]) || !isset($actionController[1])) {
                throw new AppException("Bad configuration on your routes file near $endpoint");
            }
            self::$routes[] = [
                'route' => $route,
                'controller' => $actionController[0],
                'action' => $actionController[1],
                'method' => $method,
                'via' => $via['via']
            ];
        }
    }


    /**
     * @param $route
     * @param $controller string
     * @param $actions array
     * @throws AppException
     */
    public static function resources($route, $controller, array $actions) {
        foreach($actions as $methods) {
            foreach ($methods as $method => $action) {
                $method = strtoupper($method);

                if ($method !== 'GET' && $method !== 'POST' && $method !== 'ALL') {
                    throw new AppException("Invalid method $method");
                }
                foreach ($action as $v) {
                    $path = ($v === 'index') ? '' : "/" . $v;
                    $thisRoute = "$route$path";
                    $endpoint = "$controller@$v";
                    self::all($thisRoute, $endpoint, [ 'via' => $route . '_' . $v ], $method);
                }
            }
        }
    }

    /**
     * @param $closure
     */
    public static function missing(callable $closure) {
        self::$missing = $closure;
    }


    /**
     * @return array
     */
    public function parseURL() {

        $this->uri = $url[0] = $requestedURL = '/';

        //Apache
        if (isset($_GET['url'])) {

            $requestedURL = $_GET['url'];
            unset($_GET['url']);

        // NGINX
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestedURL = $_SERVER['REQUEST_URI'];
        }

        if ($requestedURL !== '/') {

            $this->uri = trim($requestedURL, '/');
            $url = explode('/', filter_var( $this->uri , FILTER_SANITIZE_URL));

        }

        $GLOBALS['arUrl'] = $url;
        $GLOBALS['url'] = $this->uri;

        return $url;
    }

    /**
     * Parses routes and extract arguments from url
     *
     * @return bool
     */
    public function parseRoutes()
    {
        $result = false;
        $pattern = [ '/{id}/', '/\{(!(id)|[^}]+)}/' ];
        $replace = [ '[0-9]+', '(?!\d+).+' ];

        $method = $_SERVER['REQUEST_METHOD'];
        $usePattern = true;

        foreach (self::$routes as $k => $v) {
            $key = preg_replace_callback($pattern,
                function ($match) use ($pattern, $replace) {
                    return preg_replace($pattern, $replace, $match[0]);
                },
                $v['route']
            );

            if ($key !== $v['route']) {
                self::$routes[$k]['pattern'] = $key;

                /** if we have a literal match, let's assign controller and method already. */
            } elseif ($v['route'] === $this->uri) {
                $usePattern = false;
                if ($method === $v['method'] || $v['method'] === 'ALL') {
                    $this->controller = $v['controller'];
                    $this->action = $v['action'];

                    //remove all values from uri as this route doesn't contain patterns
                    foreach ($this->arUri as $keyURI => $uri) {
                        unset($this->arUri[$keyURI]);
                    }

                    $result = true;
                }
            }
        }

        /**
         * @var  $k
         * @var  $g
         * here we will match patterns and only search for routes with patterns..
         */
        if ($usePattern) {
            foreach (self::$routes as $k => $g) {
                if (isset($g['pattern']) && preg_match("@" . $g['pattern'] . "$@i", $this->uri)) {
                    if ($method === $g['method'] || $g['method'] === 'ALL') {
                        $this->controller = $g['controller'];
                        $this->action = $g['action'];

                        $arPattern = explode('/', $g['pattern']);
                        //remove all values from uri that don't represent a value or param
                        foreach ($this->arUri as $index => $splitURI) {
                            if (in_array($splitURI, $arPattern)) {
                                unset($this->arUri[$index]);
                            }
                        }
                        self::$args = $this->arUri;
                    }
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @throws AppException
     */
    private function includeRouter()
    {
        $routeFile = STORAGE_PATH . 'routes/route';

        if (!file_exists($routeFile) || ( filemtime(ROUTER_FILE) > filemtime($routeFile))) {

            if (!file_exists(STORAGE_PATH . 'routes')) {
                if ( !mkdir(STORAGE_PATH . 'routes' )) {
                    throw new AppException('Failed creating directory ' . STORAGE_PATH .
                        'routes, make sure the web server has permission to do so.');
                }
            }

            $route = file_get_contents(ROUTER_FILE);

            Interpreter::extendInterpreter('Routes', 'self', true);
            $newFile = Interpreter::parseView($route);

            if (!file_put_contents($routeFile, $newFile)) {
                throw new AppException('Failed creating routes file in ' . $routeFile .
                    ' make sure the web server has permission to do so.');
            }
        }
        include $routeFile;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed|string
     */
    public static function getRoutesByAssocKey($key, $value)
    {
        if (is_null(self::$instance)) {
            new static;
        }

        $result = '';

        foreach(self::$routes as $route) {
            if ($route[$key] === $value) {
                $result = $route;
                break;
            }
        }
        return $result;
    }

    /**
     * @return array $routes
     */
    public static function getRoutes()
    {
        if (is_null(self::$instance)) {
            new static;
        }

        $routes = [];
        foreach(self::$routes as $route) {
            $routes[] = [
                'Route' => $route['route'],
                'Method' => $route['method'],
                'Via' => $route['via']
            ];
        }
        return $routes;
    }
}