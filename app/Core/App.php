<?php
/**
 * Created By: Jon Garcia
 * Date: 1/16/16
 **/

declare(strict_types=1);

use App\Core\Console\Cli;
use App\Core\Exceptions\ErrorException;
use App\Core\Exceptions\AppException;
use App\Core\Request;
use App\Core\Http\Routing\Route;
use App\Core\Http\Routing\Router;
use App\Core\Interfaces\Advises\AfterAdvise;
use App\Core\Interfaces\Advises\BeforeAdvise;
use App\Core\Interfaces\Authenticatable;
use App\Core\Interfaces\HandleException;
use App\Core\Http\Response;
use App\Core\Console\Argv;

/**
 * Class App
 * @package App\Core
 */
class App {

    /**
     * @var array
     */
    private static $singletons = [];

    /**
     * @var string
     */
    private static $autoloader;

    /**
     * @var array
     */
    private static $settings = [];

    /**
     * @var array
     */
    private static $initialConfigs = [];

    /**
     * @var bool
     */
    private static $errorReporting = false;

    /**
     * @var array
     */
    private $handlerTypes = [
        "exception" => HandleException::class,
        "authentication" => Authenticatable::class,
        "response" =>  Response::class
    ];

    /**
     * @var array
     */
    private $handlerSetters = [
        "exception" => "setExceptionHandler",
        "authentication" => "setAuthenticationHandler",
        "response" => "setResponseHandler"
    ];

    /**
     * @var Response
     */
    private $responseHandler = Response::class;

    /**
     * @var bool
     */
    private $hasExceptionHandler = false;

    /**
     * @var string|HandleException
     */
    private $exceptionHandler;

    /**
     * @var bool
     */
    private $hasAuthenticationHandler = false;

    /**
     * @var string|Authenticatable
     */
    private $authenticationHandler;

    /**
     * @var int
     */
    private $readyState = 0;

    /**
     * @var bool
     */
    private $isWebRequest = false;

    /**
     * @var bool
     */
    private $isCLIRequest = false;

    /**
     * App constants
     */
    const APP_CONSTANTS = "app/Core/configs/app_constants";

    /**
     * App constructor.
     */
    private function __construct()
    {
        //sets some env variables
        self::setXattrSupport();

        //TODO move to a cron
        self::cleanLogFile();

        if (isset($_SERVER['SHELL'])) {
            $this->isCLIRequest = true;
        } else {
            $this->isWebRequest = true;
        }
    }

    /**
     * cleanLogFile
     * TODO move to a cron
     */
    public static function cleanLogFile()
    {
        $log = STORAGE_PATH . 'logging/app-errors.log';

        if (file_exists($log) && filesize($log) >= 100000) {
            $file = file($log);
            $file = array_splice($file, -500, 500);
            $handle = fopen($log, 'w');
            fwrite($handle, implode("", $file));
        }
    }

    /**
     * SetXattrSupport
     */
    private static function setXattrSupport() {
        $supportsExtendedAttr = (int) extension_loaded('xattr');
        putenv("XATTR_SUPPORT={$supportsExtendedAttr}");
        $osExtendedAttr = $supportsExtendedAttr ? (int) xattr_supported(FILES_PATH . 'README.txt') : 0;
        putenv("XATTR_ENABLED={$osExtendedAttr}");
    }

    /**
     * @param array $configs
     */
    public static function defineSettings(array $configs)
    {
        set_error_handler([__CLASS__, "errorHandler"]);
        self::defineAbsolutePath();
        self::$initialConfigs = $configs;
    }

    /**
     * Process config files
     */
    public static function processConfigs()
    {
        foreach (self::$initialConfigs as $key => $config) {
            self::$settings[$key] = self::import($config);
        }
    }

    /**
     * @param $setting
     * @return mixed|null
     */
    public static function getSettings(string $setting)
    {
        return self::$settings[$setting] ?? null;
    }

    /**
     * @param string $timezone
     */
    public function setTimeZone(string $timezone)
    {
        date_default_timezone_set($timezone);
    }

    /**
     * @param \Closure $closure
     * @throws AppException
     */
    public static function main(\Closure $closure)
    {
        if (is_null(self::$autoloader)) {
            throw new AppException("Please configure an autoloader class");
        }
        self::import(self::$autoloader);
        self::import(self::APP_CONSTANTS);

        $instance = new static();
        self::$singletons["app"] = $instance;
        $closure = \Closure::bind($closure, $instance);
        $closure();
    }

    /**
     * @param $handler
     * @param string $type
     * @return bool
     */
    public function registerHandler($handler, string $type)
    {
        $this->validateHandlerType($type);
        $this->validateHandler($handler, self::app()->handlerTypes[$type]);
        return $this->addHandler($handler, $type);
    }

    /**
     * @param $handler
     * @param $instance
     * @throws AppException
     */
    private function validateHandler($handler, $instance)
    {
        if ((! is_string($handler) && ! is_object($handler)) ||
            (is_object($handler) && ! $handler instanceof $instance) ||
            (is_string($handler) && ! class_exists($handler) || ! in_array(
                $instance, class_implements($handler)))) {
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            throw new AppException(
                "Handler type must be a class or an object and must implement ".$instance,
                $debug["file"],
                $debug["line"]
            );
        }
    }

    /**
     * @param $type
     * @throws AppException
     */
    private function validateHandlerType($type)
    {
        if (! array_key_exists($type, $this->handlerTypes)) {
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            throw new AppException(
                "Invalid handler type. see App::getValidHandlers()",
                $debug["file"],
                $debug["line"]
            );
        }
    }

    /**
     * @param string $class
     * @param string $type
     * @return bool
     */
    private function addHandler($class, string $type): bool
    {
        $this->{$this->handlerSetters[$type]}($class);
        return true;
    }

    /**
     * @param string $class
     */
    protected function setExceptionHandler($class)
    {
        $this->hasExceptionHandler = true;
        $this->exceptionHandler = $class;
    }

    /**
     * @param string $class
     */
    protected function setAuthenticationHandler($class)
    {
        $this->hasAuthenticationHandler = true;
        $this->authenticationHandler = $class;
    }

    /**
     * @param $class
     */
    protected function setResponseHandler($class)
    {
        if (is_object($class)) {
            $this->responseHandler = get_class($class);
        } else {
            $this->responseHandler = $class;
        }
    }

    /**
     * @return bool
     */
    public static function hasExceptionHandler(): bool
    {
        return self::app()->hasExceptionHandler;
    }

    /**
     * @return HandleException
     */
    public static function getExceptionHandler(): HandleException
    {
        $class = self::app()->exceptionHandler;
        return is_object ($class) ? $class : new $class();
    }

    /**
     * @return bool
     */
    public static function hasAuthenticationHandler(): bool
    {
        return self::app()->hasAuthenticationHandler;
    }

    /**
     * @return Authenticatable
     */
    public static function getAuthenticationHandler(): Authenticatable
    {
        $class = self::app()->authenticationHandler;
        return is_object($class) ? $class : new $class();
    }

    /**
     * @return array
     */
    public static function getValidHandlers(): array
    {
        return array_keys(self::app()->handlerTypes);
    }

    /**
     * @return bool
     */
    public function isReportingErrors(): bool
    {
        return self::$errorReporting;
    }

    /**
     * @param Request $request
     */
    public function startRouter(Request $request)
    {
        $this->readyState = 1;
        self::$singletons['router'] = new Router($request);
    }

    /**
     *
     */
    public function fireApp()
    {
        if ($this->isCLIRequest) {
            return new Cli();
        }

        if ($this->readyState === 1) {

            $route = self::getRouter()->getCurrentRoute();
            return $this->prepareArguments($route);
        } else {
            throw new \RuntimeException("App is not done bootstrapping");
        }
    }

    /**
     * @param Route $route
     * @return bool
     */
    public function prepareArguments(Route $route): bool
    {
        $arguments = $route->getVariables();
        if ($route->getType() === "callableAction") {
            $reflection = new \ReflectionParameter($route->getEndpoint(), 0);
            $this->expectedArguments($arguments, null, [$reflection]);
            return $this->callMethods($route->getEndpoint(), $arguments, $route);
        } else {
            $this->expectedArguments($arguments, $route);
            $class = $route->getController();
            return $this->callMethods([ new $class(), $route->getEndpoint()], $arguments, $route);
        }
    }

    /**
     * @param callable $method
     * @param array $arguments
     * @param Route $route
     * @return bool
     */
    private function callMethods(callable $method, array $arguments, Route $route): bool
    {
        if ($route->hasBefore()) {
            $pointcut = $this->getPointcutName("before", $method);
            /** @var BeforeAdvise $filter */
            foreach ($route->getFilters("before") as $filter) {
                $filter->handler(self::getRequest());
                if ($pointcut !== "" && method_exists($filter, $pointcut)) {
                    call_user_func_array([$filter, $pointcut], [self::getRequest()]);
                }
            }
        }
        $output = call_user_func_array($method, $arguments);

        if ($route->hasAfter()) {
            /** @var AfterAdvise $filter */
            $pointcut = $this->getPointcutName("after", $method);
            foreach ($route->getFilters("after") as $filter) {
                $filter->exitHandler($output, self::getRequest());
                if ($pointcut !== "" && method_exists($filter, $pointcut)) {
                    call_user_func_array([$filter, $pointcut], [self::getRequest()]);
                }
            }
        }
        call_user_func([$this->responseHandler, "render"], $output);
        return true;
    }

    /**
     * @param string $type
     * @param callable $method
     * @return string
     */
    private function getPointcutName(string $type, callable $method): string
    {
        if (isset($method[0])) {
            $namespace = explode("\\", get_class($method[0]));
            $class = str_replace("Controller", "", array_pop($namespace));
            return "{$type}{$class}{$method[1]}";
        }
        return "";
    }

    /**
     * @param $arguments
     * @param Route $route
     * @param array|null $reflectionParameters
     */
    public function expectedArguments(array &$arguments = [], Route $route = null, array $reflectionParameters = null)
    {
        if (is_null($reflectionParameters) && ! is_null($route)) {
            $reflection = new \ReflectionMethod($route->getController(), $route->getEndpoint());
            $reflectionParameters = $reflection->getParameters();
        }
        /** @var \ReflectionParameter $param */
        foreach ($reflectionParameters as $k => $param) {
            if (! is_null($param->getClass()) && $param->getClass()->name === Request::class) {
                $arguments[] = self::app()->getRequest();
                if (isset($arguments[$k])) {
                    $pos = count($arguments) - 1;
                    shiftElement($arguments, $pos, $k);
                }
            }
        }
    }

    /**
     *
     */
    public function startRequest()
    {
        self::$singletons['request'] = new Request();
    }

    /**
     * @return App
     */
    public static function app(): self
    {
        return self::$singletons['app'];
    }

    /**
     * @return Router
     */
    public static function getRouter(): Router
    {
        return self::$singletons['router'];
    }

    /**
     * @return Request
     */
    public static function getRequest(): Request
    {
        return self::$singletons['request'];
    }

    /**
     * Define absolute path
     */
    private static function defineAbsolutePath()
    {
        if (! defined("ABSOLUTE_PATH")) {
            define('ABSOLUTE_PATH', dirname(getcwd()) . DIRECTORY_SEPARATOR);
        }
    }

    /**
     * @param $errNo
     * @param $errStr
     * @param $errFile
     * @param $errLine
     * @throws ErrorException
     */
    public static function errorHandler($errNo, $errStr, $errFile, $errLine)
    {
        throw new ErrorException($errStr, 0, $errNo, $errFile, $errLine);
    }

    /**
     * @param string $path
     */
    public static function setAutoLoader(string $path)
    {
        self::$autoloader = $path;
    }


    public static function dd()
    {
        Kint::$display_called_from = false;
        $data = func_num_args() === 1 ? func_get_args()[0] : func_get_args();
        $currentBuffer = ob_get_clean();
        ob_start();
        !Kint::dump($data);
        $output = $currentBuffer.ob_get_clean();
        Response::render($output);
        die(1);
    }

    public static function d()
    {
        Kint::$display_called_from = false;
        $data = func_num_args() === 1 ? func_get_args()[0] : func_get_args();
        $currentBuffer = ob_get_clean();
        ob_start();
        !Kint::dump($data);
        $output = $currentBuffer.ob_get_clean();
        Kint::$display_called_from = true;
        Response::render($output);
    }

    /**
     * @param $file
     */
    public function importFile($file)
    {
        self::import($file);
    }

    /*
     *
     */
    public static function startErrorReporting()
    {
        self::$errorReporting = true;
        error_reporting(E_ALL);
        ini_set('display_errors', "On");
        ini_set('display_startup_errors', "On");
    }

    /**
     *
     */
    public static function stopErrorReporting()
    {
        self::$errorReporting = false;
        error_reporting(null);
        ini_set('display_errors', "Off");
        ini_set('display_startup_errors', "Off");
    }

    /**
     * @return false|string
     */
    public static function getEnv()
    {
        return getenv("ENV");
    }

    /**
     * @param string $key
     * @param string|null $or
     * @return string|null
     */
    public static function env(string $key, string $or = null)
    {
        $value = getenv($key);
        if (is_string($value) && ! empty($value)) {
            return $value;
        }
        return $or;
    }

    /**
     * @param string[] ...$env
     * @return bool
     */
    public static function isEnv(string ...$env)
    {
        return in_array(self::getEnv(), $env);
    }

    /**
     * @param $file
     * @return mixed
     */
    public static function import($file)
    {
        $ext = '';
        if (! self::hasExtension($file)) {
            $ext = ".php";
        }
        if (self::hasLeadingSlash($file)) {
            return require_once $file.$ext;
        }
        return require_once ABSOLUTE_PATH.$file.$ext;
    }

    /*
     * Return instance of a singleton
     */
    public static function getInstance($singleton)
    {
        if (isset(self::$singletons[$singleton])) {
            return self::$singletons[$singleton];
        }

        throw new AppException("Invalid instance name");
    }

    /**
     * @param $file
     * @return bool
     */
    public static function hasExtension($file)
    {
        return strpos($file, ".") !== false;
    }

    /**
     * @param $file
     * @return bool
     */
    public static function hasLeadingSlash($file)
    {
        return strpos($file, "/") === 0;
    }

    /**
     * @param $file
     * @return bool
     */
    public static function fileExists($file)
    {
        return file_exists(ABSOLUTE_PATH.$file);
    }

}