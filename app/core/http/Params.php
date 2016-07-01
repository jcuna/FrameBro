<?php
/**
 * Created By: Jon Garcia
 * Date: 1/16/16
 */
namespace App\Core\Http;

use App\Core\Storage\File;

/**
 * Class Params
 * @package App\Core\Http
 */
class Params
{
    /**
     * Weather there're arguments with json data.
     * @var bool
     */
    public $hasJson = false;

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
     * @var Request
     */
    public $request;

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
     * @var array
     */
    public $session;

    /**
     * Params constructor.
     */
    public function __construct()
    {
        //TODO: Add $_SERVER, and grab header info, origin, host and url should be it's own properties.

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

        $this->session = $_SESSION;

        $this->origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    }

    /**
     * If json data.
     */
    private function getJson()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (! is_null($data) || !empty($data)) {

            $this->hasJson = true;

            $_POST = array_merge($_POST, $data);
        }

        $this->decodeSerializedJson();
    }

    /**
     * Knows about @AjaxRequest and helps by decoding json serialized data
     */
    private function decodeSerializedJson()
    {
        if (isset($_POST['ajax']) && is_string($_POST['ajax'])) {

            $_POST['ajax'] = json_decode($_POST['ajax'], true);
        }
    }

    /**
     * Builds up request by analysing dada inside $_ super globals
     */
    private function buildRequest()
    {
        $request = new Request();

        $this->setGlobalFiles();

        $this->setGlobalPost($request);

        $this->setGlobalGet($request);

        $this->request = $request;
    }

    /**
     *
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
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {

        // We use this form to check if isset
        // to avoid using the magic __isset
        if (!property_exists($this, $property)) {

            // Here, we're ok using magic __isset
            if (isset($this->{$property})) {
                return $this->request->{$property};
            }
        }

        return null;
    }

    /**
     * Return value of key
     *
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->request->{$key};
    }

    /**
     * Weather this key exists
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        $bool = false;

        if (isset($this->request->{$key})) {
            $bool = true;
        }

        return $bool;
    }


    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        $propertyExists = isset($this->{$name});

        if (!$propertyExists) {
            return isset($this->request->{$name});
        } else {
            return $propertyExists;
        }
    }

    /**
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        $this->request->{$property} = $value;
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
    public function all()
    {
        $result = [];
        foreach( $this->request as $property => $value) {
            $result[$property] = $value;
        }
        return $result;
    }

    /**
     * Alias to all()
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
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
}
