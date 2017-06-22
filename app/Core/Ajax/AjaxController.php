<?php
/**

 * Author: Jon Garcia
 * Date: 1/25/16
 * Time: 7:07 PM
 */

namespace App\Core\Ajax;

use App\Core\Interfaces\BroExceptionsInterface;
use App\Core\Html\DomElement;
use App\Core\Http\Controller;
use App\Core\Request;
use App\Core\Http\JsonResponse;
use App;

/**
 * Class AjaxController
 * @package App\Core\Ajax
 */
class AjaxController extends Controller
{
    /**
     * Weather there's an ajax call in progress
     *
     * @var boolean
     */
    private static $ajaxCallInProgress = false;

    /**
     * Holds data to be returned back to front end client before JSON serialization.
     *
     * @var array | string
     */
    protected static $result;

    /**
     * Holds the http status code
     *
     * @var integer
     */
    protected static $status;

    /**
     * Holds the redirect url to be sent to the front end if any.
     *
     * @var string
     */
    public static $redirect;

    /**
     * @output JsonResponse string
     */
    public static function jsonResponder()
    {
        self::AjaxHandler();

        $extra = [];

        if (!is_null(self::$redirect)) {
            $extra['redirect'] = self::$redirect;
        }

        if (AjaxRequest::hasJQueryMethodOverride()) {
            $extra['jQueryMethodOverride'] = AjaxRequest::getJQueryMethodOverride();
        }

        JsonResponse::Response(self::$result, self::$status, $extra);

        return;
    }

    /**
     * Handles the ajax request and returns the processed data.
     */
    private static function AjaxHandler()
    {
        self::$ajaxCallInProgress = true;

        $params = self::getRequestParams();

        $class = $params['ajax']['class'];
        $controller = new $class;

        try {
            if (method_exists($controller, $params['ajax']['callback'])) {

                try {
                    $action = $params['ajax']['callback'];
                    $args = [$params];
                    $reflection = new \ReflectionMethod($controller, $action);
                    App::app()->expectedArguments($args, null, $reflection->getParameters());
                    self::$result = call_user_func_array([$controller, $action], $args);
                    self::$status = 200;
                } catch (BroExceptionsInterface $e) {
                    App::dd($e);
                }
            } else {
                self::$result = 'Invalid callback method' . $params['ajax']['callback'];
                self::$status = 400;
                $cb = $params['ajax']['callback'];
                trigger_error('Invalid callback method ' . $cb . ' in ' . get_class($controller), E_USER_ERROR);
            }

            return;
        } catch (\Throwable $t) {
            App::dd($t);
        }
    }

    /**
     * returns bool
     */
    public static function ajaxCallInProgress()
    {
        return self::$ajaxCallInProgress;
    }

    /**
     * @return array|null
     */
    private static function getRequestParams()
    {
        if (self::setRequestParams()) {
            return App::getInstance("request")->toArray();
        } else {
            self::$result = 'Invalid ajax call';
            self::$status = 400;
            self::jsonResponder();
            return null;
        }
    }

    /**
     * Set params to the Params classes
     *
     * @return bool
     */
    private static function setRequestParams()
    {
        $json = Request::getJsonInput();

        if (!is_null($json) && !empty($json)) {
            Request::setJsonInput($json);
        }

        if (Request::postHas('ajax')) {
            $params = Request::postGet('ajax');
            if (is_string($params)) {
                $params = json_decode($params, true);
            }
            Request::addPersistentAttributes('ajax', $params);
            self::collapseDomElement();
            return true;
        }

        return false;
    }

    /*
     * Collapses element key from the dom into an xml object.
     */
    private static function collapseDomElement()
    {
        if (Request::postHas('element')) {
            $element = new DomElement(Request::postGet('element'));
            Request::addPersistentAttributes('element', $element);
        }
    }
}