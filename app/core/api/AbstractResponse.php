<?php
/**
 * Author: Jon Garcia
 * Date: 8/25/16
 * Time: 10:31 AM
 */

namespace App\Core\Api;


abstract class AbstractResponse
{
    /**
     * Contain the headers to be sent
     * 
     * @var array
     */
    private static $headers = [];

    /**
     * Contain response code
     * 
     * @var int
     */
    private static $responseCode = 200;

    /**
     * Contain body or content of response
     * 
     * @var string
     */
    private static $content = '';

    /**
     * Contain all valid response codes
     * 
     * @var array
     */
    private static $validStatusCodes = [
        100 => "Continue",
        200 => "OK",
        201 => "Created",
        204 => "No Content",
        206 => "Partial Content",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        307 => "Temporary Redirect",
        308 => "Permanent Redirect",
        404 => "Not Found",
        410 => "Gone",
        412 => "Precondition Failed",
        451 => "Unavailable For Legal Reasons",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout"
    ];

    /**
     * Render content
     * 
     * @param null $content
     */
    public static function render($content = null)
    {
        if ($content !== null) {
            self::setContent($content);
        }
        
        http_response_code(self::$responseCode);
        foreach (self::$headers as $headerName => $headerValue) {
            header($headerName.': '.$headerValue);
        }
        self::searchForContent();
        echo self::$content;
        exit;
    }

    /**
     * Set custom headers one at a time
     * 
     * @param $name
     * @param $value
     */
    public static function setHeader($name, $value)
    {
        self::$headers[$name] = $value;
    }

    /**
     * set batch of headers
     * 
     * @param array $headers
     */
    public static function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            self::$headers[$name] = $value;
        }
    }

    /**
     * Set a valid response code
     * 
     * @param $responseCode
     */
    public static function setResponseCode($responseCode)
    {
        if (array_key_exists($responseCode, self::$validStatusCodes)) {
            self::$responseCode = $responseCode;
        }
    }

    /**
     * Set content or body of response
     * 
     * @param $content
     */
    public static function setContent($content) {
        if (is_array($content) || $content instanceof Jsonable || $content instanceof Arrayable) {

            if (!isset(self::$headers['Content-type'])) {
                self::setHeader('Content-type', 'application/json');
            }
            
            switch ($content) {
                case $content instanceof Jsonable:
                    self::$content = $content->toJson();
                    break;
                case $content instanceof Arrayable:
                    self::$content = json_encode($content->toArray());
                    break;
                default:
                    self::$content = json_encode($content);
            }
        } elseif (is_string($content) || method_exists($content, "__toString")) {
            self::$content = $content;            
        }
    }

    /**
     * Search for content
     */
    protected function searchForContent()
    {
        if (self::$content === '') {
            self::setResponseCode(204);
        }
    }
}