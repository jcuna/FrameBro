<?php
/**

 * Author: Jon Garcia
 * Date: 1/25/16
 * Time: 7:07 PM
 */

namespace App\Core\Ajax;

use App\Core\Api\BroExceptionsInterface;
use App\Core\Html\DomElement;
use App\Core\Http\Controller;
use App\Core\Http\Params;
use App\Core\JsonResponse;

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

        if (method_exists($controller, $params['ajax']['callback'])) {
            try {
                $action = $params['ajax']['callback'] . '{action}';
                self::$result = call_user_func_array(array($controller, $action), array($params));
                self::$status = 200;
            } catch ( BroExceptionsInterface $e ) {
                self::$result = !ddd($e);
                self::$status = 500;
            }
        } else {
            self::$result = 'Invalid callback method' . $params['ajax']['callback'] ;
            self::$status = 400;
            $cb = $params['ajax']['callback'];
            trigger_error('Invalid callback method ' . $cb . ' in ' . get_class($controller) , E_USER_ERROR);
        }

        return;
    }

    /**
     * returns bool
     */
    public static function ajaxCallInProgress()
    {
        return self::$ajaxCallInProgress;
    }

    /**
     * @return array|void
     */
    private static function getRequestParams()
    {
        if (self::setRequestParams()) {
            return (new Params())->toArray();
        } else {
            self::$result = 'Invalid ajax call';
            self::$status = 400;
            self::jsonResponder();
            return;
        }
    }

    /**
     * Set params to the Params classes
     *
     * @return bool
     */
    private static function setRequestParams()
    {
        $json = Params::getJsonInput();

        if (!is_null($json) && !empty($json)) {
            Params::setJsonInput($json);
        }

        if (Params::postHas('ajax')) {
            $params = Params::postGet('ajax');
            if (is_string($params)) {
                $params = json_decode($params, true);
            }
            Params::addPersistentAttributes('ajax', $params);
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
        if (Params::postHas('element')) {
            $element = new DomElement(Params::postGet('element'));
            Params::addPersistentAttributes('element', $element);
        }
    }
}