<?php
/**
 * Author: Jon Garcia.
 * Date: 2/16/17
 * Time: 9:22 PM
 */

namespace App\Core\Http\Routing;

use App\Controllers\Controller;
use App\Core\Exceptions\RouteException;
use App\Core\Interfaces\Advises\AfterAdvise;
use App\Core\Interfaces\Advises\AroundAdvise;
use App\Core\Interfaces\Advises\BeforeAdvise;

class Route
{
    /**
     * @var string
     */
    private $route;

    /**
     * @var string|null
     */
    private $controller;

    /**
     * @var string|callable
     */
    private $endpoint;

    /**
     * @var string
     */
    private $type;

    /**
     * @var \ArrayIterator
     */
    private $options;

    /**
     * @var string
     */
    private $httpMethod;

    /**
     * @var array
     */
    private $variables = [];

    /**
     * @var bool
     */
    private $hasBefore = false;

    /**
     * @var bool
     */
    private $hasAfter = false;

    /**
     * @var bool
     */
    private $hasAround = false;

    /**
     * @var array
     */
    private static $filters = [
        "before" => [],
        "after" => [],
        "around" => []
    ];

    /**
     * @param string $type
     * @return array
     * @throws RouteException
     */
    public static function getFilters(string $type): array
    {
        if (isset(self::$filters[$type])) {
            return self::$filters[$type] + self::$filters["around"];
        }
        throw new RouteException("Invalid filter type");
    }

    /**
     * @return bool
     */
    public function hasBefore(): bool
    {
        return $this->hasBefore || $this->hasAround();
    }

    /**
     * @return bool
     */
    public function hasAfter(): bool
    {
        return $this->hasAfter || $this->hasAround();
    }

    /**
     * @return bool
     */
    public function hasAround(): bool
    {
        return $this->hasAround;
    }

    /**
     * @var array
     */
    private static $pattern = ['/{id}/', '/\{(!(id)|[^}]+)}/'];

    /**
     * @var array
     */
    private static $replace = ['[0-9]+', '(?!\d+).+'];

    /**
     * Route constructor.
     * @param string $route
     * @param $endpoint
     * @param array $options
     * @param string $httpMethod
     */
    public function __construct(string $route, $endpoint, array $options, string $httpMethod)
    {
        $this->route = $route;
        $this->setOptions($options);
        $this->httpMethod = $httpMethod;
        $this->resolveAction($endpoint);
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = new \ArrayIterator([
            "via" => null,
            "before" => null,
            "after" => null,
            "around" => null
        ]);

        $validOptions = array_intersect_key($options, (array) $this->options);
        foreach ($validOptions as $key => $option) {
            if (method_exists($this, $key)) {
                call_user_func([$this, $key], $option);
            }
        }
    }

    /**
     * @param $endpoint
     * @throws RouteException
     */
    public function resolveAction($endpoint)
    {
        if (is_callable($endpoint)) {
            $this->type = "callableAction";
            $this->endpoint = $endpoint;

        } else {
            $this->type = "controllerAction";
            $actionController = explode('@', $endpoint);
            if (!isset($actionController[0]) || !isset($actionController[1])) {
                throw new RouteException("Bad configuration on your routes file near $endpoint");
            }
            $this->setController($actionController[0]);
            $this->setControllerAction($actionController[1]);
        }
    }

    /**
     * @param string $controller
     * @throws RouteException
     */
    private function setController(string $controller)
    {
        $namespace = Controller::$namespace."\\";
        if (class_exists($namespace.$controller."Controller")) {
            $this->controller = $namespace.$controller."Controller";
        } elseif (class_exists($namespace.$controller)) {
            $this->controller = $namespace.$controller;
        } elseif (class_exists($controller)) {
            $this->controller = $controller;
        } else {
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)[4];
            throw new RouteException(
                "Invalid defined controller {$controller} at line {$debug['line']} on {$debug['file']}");
        }
    }


    private function setControllerAction(string $action)
    {
        if (method_exists($this->controller, $action)) {
            $this->endpoint = $action;
        } else {
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)[4];
            throw new RouteException(
                "Invalid method `{$action}` declared for {$this->controller} at line {$debug['line']} on {$debug['file']}");
        }
    }

    /**
     * @param string $advise
     * @return Route
     */
    public function before(string $advise): Route
    {
        return $this->addFilter("before", $advise, BeforeAdvise::class);
    }

    /**
     * @param string $advise
     * @return Route
     */
    public function after(string $advise): Route
    {
        return $this->addFilter("after", $advise, AfterAdvise::class);
    }

    /**
     * @param string $advise
     * @return Route
     */
    public function around(string $advise): Route
    {
        return $this->addFilter("around", $advise, AroundAdvise::class);
    }

    /**
     * @param string $alias
     */
    public function via(string $alias)
    {
        $this->options["via"] = $alias;
    }

    /**
     * @throws RouteException
     */
    private function throwInvalidAdviseError()
    {
        $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
        $i = 2;
        if (isset($debug[4]) && $debug[4]['function'] === "setOptions") {
            $i = 7;
        } elseif (isset($debug[7]) && $debug[7]['function'] === "group") {
            $i = 7;
        }
        throw new RouteException(
            "Invalid filter declared at line {$debug[$i]['line']} on {$debug[$i]['file']}");
    }

    /**
     * @param string $type
     * @param string $class
     * @param string $implements
     * @return Route
     */
    private function addFilter(string $type, string $class, string $implements): Route
    {
        if (class_exists($class) && in_array($implements, class_implements($class))) {
            $method = ucfirst($type);
            $this->{"has{$method}"} = true;
            if (! isset(self::$filters[$type][$class])) {
                self::$filters[$type][$class] = new $class;
            }
        } else {
            $this->throwInvalidAdviseError();
        }
        return $this;
    }

    /**
     * @param string $uri
     * @return bool
     */
    public function match(string $uri)
    {
        if ($uri === $this->route) {
            return true;
        }

        $key = preg_replace_callback(self::$pattern, function ($match) {
                return preg_replace(self::$pattern, self::$replace, $match[0]);
            }, $this->route
        );
        $matches = (bool) preg_match("@^{$key}$@i", $uri, $match);

        if ($matches) {
            $this->setVariables($uri);
        }
        return $matches;
    }

    /**
     * @param $uri
     */
    private function setVariables($uri)
    {
        $arUri = $uri === "/" ? ["/"] :
            explode('/', filter_var(
                    $uri, FILTER_SANITIZE_URL
                ));

        $arRoute = $this->route === "/" ? ["/"] :
            explode('/', filter_var(
                $this->route, FILTER_SANITIZE_URL
            ));

        foreach ($arUri as $k => $part) {
            if ($part !== $arRoute[$k]) {
                $this->variables[] = $part;
            }
        }
    }

    /**
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @param string $method
     * @return bool
     */
    public function httpMethodMatch(string $method): bool
    {
        if ($this->httpMethod === $method || $this->httpMethod === "All") {
            return true;
        }
        return false;
    }

    /**
     * @return null|string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return callable|string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return (array) $this->options;
    }
}