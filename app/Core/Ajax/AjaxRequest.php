<?php
/**
* Author: Jon Garcia
* Date: 1/25/16
**/

namespace App\Core\Ajax;


use App\Core\Exceptions\AppException;
use App\Core\Request;
use App\Core\Http\Routing\Router;

class AjaxRequest
{
    /**
     * Holds the ajax request processing data.
     *
     * @var array
     */
    private static $Queue = array();

    /**
     * If current call wants to override jQuery method
     *
     * @var string
     */
    private static $jQueryMethodOverride;

    /**
    |-------------------------------------------------------------------------------------------------------
    | ‘callback’        => ‘deleteUsers’, //the callback method to process the request.
    |                       Must be declared public method within the calling class,
    |                       or the class specified in the request if any.
    |
    | 'class'           => 'The name-spaced class that declared the method declared above.
    |                       Defaults to the origin controller.
    |
    | 'selector'        => '#deleteUser', //the id or class name of the
    |                       element that triggers the ajax call
    |
    | 'event'           => 'click', //the type of jQuery event triggering the action
    |                       {Defaults to click}
    |
    | 'effect'          => 'fadeIn', //the effect to be used when updating dom element {Defaults to show}
    |                       @link https://api.jquery.com/category/effects/
    |
    | 'wrapper'         => '.element-parent', //the id or class name of the parent element
    |                       where response will be added. i.e. #id, .class
    |
    | ‘jQueryMethod’    => 'replaceWith' // the jquery method to use to insert the
    |                       content in the dom. Defaults to {replaceWith}
    |                       replaceWith will also replace the wrapper,
    |                       use the html jquery method to empty and
    |                       insert or wrap the returning view
    |                       into the same wrapper.
    |                       @link http://api.jquery.com/category/manipulation/
    |
    | ‘jsCallback’      => 'false' // if specified, upon ajax response, the response will
    |                       be sent as argument to the specified javascript callback.
    |                       Specify namespaces following dot convention. i.e. "MyObject.myFunction"
    |
    | 'httpMethod'     => ‘get’ // the http request method to use. {defaults to post}
    |-------------------------------------------------------------------------------------------------------
    */
    /**
     * @param array $data
     * @throws AppException
     */
	public static function ajaxQueue(array $data)
	{
        if (!isset($data['class'])) {
            if (debug_backtrace()[1]['function'] === 'ajaxRequest' && isset(debug_backtrace()[2]['class'])) {
                $data['class'] = debug_backtrace()[2]['class'];
            } elseif (isset(debug_backtrace()[1]['class'])) {
                $data['class'] = debug_backtrace()[1]['class'];
            }
        }

        self::validateAjaxRequest($data);

        $data['jQueryMethod'] = isset($data['jQueryMethod']) ? $data['jQueryMethod'] : 'replaceWith';
        $data['jsCallback']  = isset($data['jsCallback']) ? $data['jsCallback'] : false;
        $data['httpMethod'] = isset($data['httpMethod']) ? $data['httpMethod'] : 'POST';
        $data['effect']    = isset($data['effect']) ? $data['effect'] : 'show';
        $data['event']    = isset($data['event']) ? $data['event'] : 'click';
        $data['args']   = array_values(Router::getInstance()->getCurrentRoute()->getVariables());
        $data['url']   = Request::$uri;

        self::addToQueue($data);
	}

    /**
     * @return string
     */
    public static function getAjaxObject() {

        $output = '<script>';
        $output .= '$.extend(bro.settings, ';

        $arAjaxData['Ajax'] = self::$Queue;
        $output .= json_encode($arAjaxData);

        $output .= ');</script>';

        return $output;

    }

    /**
     * Overrides current call jQuery method
     *
     * @param string $jQueryMethod
     */
    public static function jQueryMethod($jQueryMethod = 'replaceWith')
    {
        self::$jQueryMethodOverride = $jQueryMethod;
    }

    /**
     * Whether jQuery method is overridden
     *
     * @return bool
     */
    public static function hasJQueryMethodOverride()
    {
        if (!is_null(self::$jQueryMethodOverride)) {
            return true;
        }

        return false;
    }

    /**
     * Get jquery override method
     *
     * @return string
     */
    public static function getJQueryMethodOverride()
    {
        return self::$jQueryMethodOverride;
    }

    /**
     * @param $data
     */
    private static function addToQueue($data)
    {
        self::$Queue[] = $data;
    }

    /**
     * @param $data
     * @throws AppException
     */
    private static function validateAjaxRequest($data)
    {
        if (!isset($data['class'])) {
            throw new AppException('AjaxCall: You are originating an ajax call from a non class, you must specify a class.');
        }

        $validateArray = [ 'callback', 'selector', 'wrapper'];

        foreach ( $validateArray as $k ) {
            if (!isset($data[$k])) {
                throw new AppException("Missing key $k from array");
            }
        }
    }
}
