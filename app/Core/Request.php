<?php
/**
 * Created By: Jon Garcia
 * Date: 1/16/16
 */
namespace App\Core;

use App\Core\Interfaces\Arrayable;
use App\Core\FileHandler\File;
use App\Core\Interfaces\HasArguments;
use App\Core\Http\Session;

/**
 * Class Params
 * @package App\Core\Http
 */
class Request implements Arrayable, HasArguments
{
    /**
     * Weather there're arguments with json data.
     * @var bool
     */
    private static $hasJson = false;

    /**
     * Files uploaded
     *
     * @var File
     */
    public $files;

    /**
     * Weather there is data in the request.
     *
     * @var bool
     */
    public $empty = true;

    /**
     * The request data
     *
     * @var Arguments
     */
    public $arguments;

    /**
     * Server super global
     *
     * $var array
     */
    public $server;

    /**
     * Contains the request headers
     * @var array
     */
    public $headers;

    /**
     * The origin url for async request
     *
     * @var string
     */
    public $origin;

    /**
     * Request cookies
     *
     * @var array
     */
    public $cookies;

    /**
     * Request session
     *
     * @var Session
     */
    public $session;

    /**
     * @var string
     */
    public static $uri;

    /**
     * Stores attributes that have been added externally
     *
     * @var array
     */
    private static $persistentAttributes = [];

    /**
     * @var Request
     */
    private static $instance;

    /**
     * Params constructor.
     */
    public function __construct()
    {
        self::$instance = $this;
        $this->addEnvArguments();
        $this->getJson();
        $this->buildRequest();
    }

    /**
     * Add environment arguments and values
     */
    private function addEnvArguments()
    {
        $this->server = $_SERVER;
        $this->headers = getallheaders();
        $this->cookies = $_COOKIE;
        $this->session = new Session();
        $this->origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    }

    /**
     * If json data.
     */
    private function getJson()
    {
        if (self::$hasJson) {
            return;
        }

        $data = self::getJsonInput();

        if (!is_null($data) && !empty($data)) {
            self::setJsonInput($data);
        }
    }

    /**
     * @return array
     */
    public static function getJsonInput()
    {
        return json_decode(file_get_contents("php://input"), true);
    }

    /**
     * @param array $json
     */
    public static function setJsonInput(array $json)
    {
        self::$hasJson = true;

        $_POST = array_merge($_POST, $json);
    }

    /**
     * Builds up request by analysing dada inside $_ super globals
     */
    private function buildRequest()
    {
        $request = new Arguments();

        $this->setGlobalFiles();
        $this->setGlobalPost($request);
        $this->setGlobalGet($request);
        $this->setPersistentAttributes($request);

        $this->arguments = $request;
    }

    /**
     * Set files within $_FILES super global
     */
    private function setGlobalFiles()
    {
        $file = null;
        if ($_FILES) {
            foreach ($_FILES as $name => $param) {

                $file = new File($param['name']);
                $file->{$name} = $param;
            }

            $this->files = $file;
            $this->empty = false;

            foreach($_FILES as $key => $file) {

                $_POST[$key] = $file['name'];

            }
        }
    }

    /**
     * @param $key
     */
    public function unsetParam($key)
    {
        if (isset($_GET[$key])) {
            unset($_GET[$key]);
        } elseif (isset($_POST[$key])) {
            unset($_POST[$key]);
        } elseif (isset($_FILES[$key])) {
            unset($_FILES[$key]);
        }
    }

    /**
     * @return string
     */
    public function getRequestUri(): string
    {
        if (isset(self::$uri)) {
            return self::$uri;
        }

        self::$uri = $url[0] = $requestedURL = '/';
        //Apache
        if (isset($_GET['url'])) {
            $requestedURL = $this->get('url');
            $this->unsetParam("url");
        // NGINX
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestedURL = $_SERVER['REQUEST_URI'];
        }

        if ($requestedURL !== '/') {
            self::$uri = trim($requestedURL, '/');
        }
        return self::$uri;
    }

    /**
     * @param $prefix
     * @return bool
     */
    public static function urlHasPrefix($prefix)
    {
        $prefix = trim($prefix, "/");
        return strpos(self::$instance->getRequestUri(), $prefix) !== false;
    }

    /**
     * Set attributes within $_POST
     *
     * @param $request
     */
    private function setGlobalPost($request)
    {
        if ($_POST) {
            foreach ($_POST as $name => $param) {
                $request->{$name} = $param;
            }
            $this->empty = false;
        }
    }

    /**
     * Set attributes within $_GET
     *
     * @param $request
     */
    private function setGlobalGet($request)
    {
        if ($_GET) {
            foreach ($_GET as $name => $param) {
                $request->{$name} = $param;
            }
            $this->empty = false;
        }
    }

    /**
     * @param $key
     * @return bool
     */
    public static function postHas($key) {
        return isset($_POST[$key]);
    }

    /**
     * @param $key
     * @return bool
     */
    public static function getHas($key) {
        return isset($_GET[$key]);
    }

    /**
     * @param $key
     * @return bool
     */
    public static function fileHas($key) {
        return isset($_FILES[$key]);
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function postGet($key) {
        return $_POST[$key];
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function getGet($key) {
        return $_GET[$key];
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function fileGet($key) {
        return $_FILES[$key];
    }

    /**
     * @param $cookie
     * @return bool
     */
    public static function hasCookie($cookie)
    {
        return isset($_COOKIE[$cookie]);
    }

    /**
     * @param $cookie
     * @return string
     */
    public static function getCookie($cookie)
    {
        return $_COOKIE[$cookie];
    }


    /**
     * @param $request
     */
    private function setPersistentAttributes($request)
    {
        foreach (self::$persistentAttributes as $attribute => $value) {
            $request->{$attribute} = $value;
        }
    }

    /**
     * @param $property
     * @return mixed
     */
    public function __get(string $property)
    {
        // We use this form to check if isset
        // to avoid using the magic __isset
        if (!property_exists($this, $property)) {

            return self::$persistentAttributes[$property] ?? $this->arguments->{$property};
        }

        return null;
    }

    /**
     * Return value of key
     *
     * @param $key
     * @return mixed
     */
    public function get(string $key)
    {
        return self::$persistentAttributes[$key] ?? $this->arguments->{$key};
    }

    /**
     * Convert a param string date into a date object
     *
     * @param $key
     * @return \DateTime
     */
    public function getAsDateObject($key)
    {
        return new \DateTime($this->arguments->{$key});
    }

    /**
     * Return a sql datetime string
     *
     * @param $key
     * @return string
     */
    public function getAsSqlDate($key)
    {
        return sqlTime($this->arguments->{$key});
    }

    /**
     * Weather this key exists
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset(self::$persistentAttributes[$key]) || isset($this->arguments->{$key});
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        $propertyExists = isset($this->{$name});

        if (!$propertyExists) {
            return isset($this->arguments->{$name});
        } else {
            return $propertyExists;
        }
    }

    /**
     * @param $property
     * @param $value
     */
    public function __set(string $property, $value)
    {
        $this->arguments->{$property} = $value;
    }

    /**
     * @param $filename
     * @return bool
     */
    public function has_file($filename)
    {
        $result = false;

        if (isset($this->files->{$filename} )) {
            $file = $this->files->{$filename};
            $result = $file['error'] === 0 ? TRUE : false;
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->server["REQUEST_METHOD"];
    }


    /**
     * Alias for has_file
     *
     * @param $filename
     * @return bool
     */
    public function hasFile($filename)
    {
        return $this->has_file($filename);
    }

    /**
     * Gets all params into array
     * @return array
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->arguments as $property => $value) {
            if ($value instanceof Arrayable) {
                $result[$property] = $value->toArray();
            } else {
                $result[$property] = $value;
            }
        }
        foreach(self::$persistentAttributes as $attr => $value) {
            if ($value instanceof Arrayable) {
                $result[$attr] = $value->toArray();
            } else {
                $result[$attr] = $value;
            }
        }
        return $result;
    }

    /**
     * Alias to all()
     *
     * @return array
     */
    public function all()
    {
        return $this->toArray();
    }

    /**
     * Add parameters and make them persist throughout instances.
     *
     * Override any existing params with same property name
     *
     * @param $key
     * @param $value
     */
    public static function addPersistentAttributes($key, $value)
    {
        self::$persistentAttributes[$key] = $value;
    }

    /**
     * Destroys all request data.
     */
    public function destroy()
    {
        $_POST = array();
        $_GET = array();
        $_FILES = array();
    }

    public function isEmpty(): bool
    {
        return $this->empty;
    }

    public function arguments(): Arguments
    {
        return $this->arguments;
    }
}
