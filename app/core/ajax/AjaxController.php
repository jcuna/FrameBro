<?php
/**

 * Author: Jon Garcia
 * Date: 1/25/16
 * Time: 7:07 PM
 */

namespace App\Core\Ajax;

use App\Core\Api\BroExceptionsInterface;
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
    public function jsonResponder()
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

        $params = self::getParams();

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
     * @return Params|array
     */
    private static function getParams()
    {
        $params = (new Params())->all();

        //skips the respond altogether if no ajax data
        if (!isset($params['ajax'])) {
            self::$result = 'Invalid ajax call';
            self::$status = 400;
            self::jsonResponder();
        }

        return $params;
    }
}