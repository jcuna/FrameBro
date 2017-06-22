<?php
/**
 * Author: Jon Garcia
 * Date: 2/5/16
 * Time: 9:25 AM
 */

namespace App\Core\Http\Routing;

use App;
use App\Core\EventReceiver;
use App\Core\Exceptions\HttpMethodException;
use App\Core\Exceptions\HttpNotFoundException;
use App\Core\Exceptions\RouteException;
use App\Core\Request;

/**
 * Class Routes
 * @package App\Core\Http
 */
class Router
{
    /**
     * All the routes registered routes
     * @var array $routes
     */
    private static $routes = [];

    /**
     * @var Route
     */
    private $currentRoute;

    /**
     * If declared in the routes, contains the action to be taken when a page is missing.
     * @var callable | string
     */
    private static $missing;

    /**
     * The registered controller
     *
     * @var
     */
    public $controller;

    /**
     * @var Request
     */
    public $request;

    /**
     * Reference to itself
     *
     * @var Router
     */
    private static $instance;

    /**
     * @var array
     */
    private static $groupOptions = [];

    /**
     * Routes constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        // Includes the router file.
        App::import(ROUTER_FILE);
        self::$instance = $this;
    }

    /**
     * @return mixed
     * @throws HttpNotFoundException
     */
    public function callMissingPage()
    {
        if (!empty(self::$missing)) {
            return call_user_func(self::$missing, $this->request);
        } else {
            throw new HttpNotFoundException("Page not found");
        }
    }

    /**
     * @param array $options
     * @param callable $callable
     */
    public static function group(array $options, callable $callable)
    {
        self::$groupOptions = $options;
        $callable();
        self::$groupOptions[] = [];
    }

    /**
     * @return Router
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * @param string $route
     * @param string $endpoint
     * @param array $options
     * @return Route
     */
    public static function put(string $route, $endpoint, array $options = []) {
        return self::addRoute($route, $endpoint, $options, 'PUT');
    }

    /**
     * @param string $route
     * @param string $endpoint
     * @param array $options
     * @return Route
     */
    public static function delete(string $route, $endpoint, array $options = []) {
        return self::addRoute($route, $endpoint, $options, 'DELETE');
    }

    /**
     * @param string $route
     * @param string $endpoint
     * @param array $options
     * @return Route
     */
    public static function get(string $route, $endpoint, array $options = []) {
        return self::addRoute($route, $endpoint, $options, 'GET');
    }

    /**
     * @param string $route
     * @param string $endpoint
     * @param array $options
     * @return Route
     */
    public static function post(string $route, $endpoint, array $options = []) {
        return self::addRoute($route, $endpoint, $options, 'POST');
    }

    /**
     * @param string $route
     * @param $endpoint
     * @param array $options
     * @return Route
     */
    public static function all(string $route, $endpoint, array $options = []) {
        return self::addRoute($route, $endpoint, $options, 'All');
    }

    /**
     * @param string $route
     * @param $endpoint
     * @param array $options
     * @param string $method
     * @return Route
     */
    private static function addRoute(string $route, $endpoint, array $options = [], string $method)
    {
        if (isset(self::$groupOptions["prefix"])) {
            $route = rtrim(self::$groupOptions['prefix'], "/")."/".$route;
        }
        $routeObj = new Route($route, $endpoint, $options, $method);
        foreach (self::$groupOptions as $key => $value) {
            if (method_exists($routeObj, $key)) {
                call_user_func([$routeObj, $key], $value);
            }
        }
        self::$routes[] = $routeObj;
        return $routeObj;
    }

    /**
     * @param string $route
     * @param string $controller
     * @param array $actions
     * @param array $options
     * @throws HttpMethodException
     */
    public static function resources(string $route, string $controller, array $actions, array $options = []) {
        foreach($actions as $methods) {
            foreach ($methods as $method => $action) {
                $method = strtoupper($method);

                if ($method !== 'GET' && $method !== 'POST' && $method !== 'ALL') {
                    throw new HttpMethodException("Invalid http method $method");
                }
                foreach ($action as $v) {
                    $path = ($v === 'index') ? '' : "/" . $v;
                    $thisRoute = "$route$path";
                    $endpoint = "$controller@$v";
                    $options['via'] = $route.'_'.$v;
                    self::addRoute($thisRoute, $endpoint, $options, $method);
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
     * @param string $alias
     * @return string
     * @throws RouteException
     */
    public static function getPath(string $alias): string
    {
        /** @var Route $route */
        foreach (self::$routes as $route) {
            if ($route->getOptions()["via"] === $alias) {
                return $route->getRoute();
            }
        }

        throw new RouteException("Invalid path alias given");
    }

    /**
     * @return Route|mixed
     */
    public function getCurrentRoute()
    {
        if (! is_null($this->currentRoute)) {
            return $this->currentRoute;
        }

        $uri = $this->request->getRequestUri();
        /** @var Route $route */
        foreach (self::$routes as $route) {
            if ($route->match($uri)) {
                $this->currentRoute = $route;
                if ($route->httpMethodMatch(
                    $this->request->getHttpMethod())) {
                    if (EventReceiver::listeningTo("routeMatched")) {
                        EventReceiver::sendEvent("routeMatched", $this->currentRoute);
                    }
                    return $this->currentRoute;
                }
            }
        }
        return $this->returnAppropriateError();
    }

    /**
     * @return mixed
     * @throws HttpMethodException
     */
    public function returnAppropriateError()
    {
        if (! is_null($this->currentRoute)) {
            throw new HttpMethodException("Method not available");
        }
        return $this->callMissingPage();
    }

    /**
     * @return array $routes
     */
    public static function getRoutes()
    {
        $routes = [];
        /** @var Route $route */
        foreach(self::$routes as $route) {
            $routes[] = [
                'Route' => $route->getRoute(),
                'Method' => $route->getHttpMethod(),
                'Options' => $route->getOptions()
            ];
        }
        return $routes;
    }
}