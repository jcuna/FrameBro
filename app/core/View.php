<?php
/**
 * Class View
 * Created By: Jon Garcia
 * Provides the methods all views will have
 */

namespace App\Core;

use App\Core\Ajax\AjaxController;
use App\Core\Api\AbstractView;
use App\Core\Exceptions\ViewException;
use App\Core\Http\Routes;

/**
 * Class View
 * @package App\Core
 */
class View extends AbstractView
{

    /**
     * Including extra general purpose functions
     */
    static function init()
    {
        include_once(CORE_PATH . 'view_helpers/general_functions.php');
    }

    /**
     * @param $viaName
     * @return string representing the route
     */
    public static function getRoute($viaName )
    {
        $route = Routes::getRoutesByAssocKey('via', $viaName);
        $result = $route['route'] === '/' ? $route['route'] : '/' . $route['route'];
        return $result;
    }

    /**
     * Fires the proper view render method based on type of call.
     * 
     * @param null $view
     * @param array $data
     * @param int $responseCode
     * @return string
     * @throws ViewException
     * @throws \Exception
     */
    public static function render( $view = null, $data = array(), $responseCode = 200 )
    {
        if (AjaxController::ajaxCallInProgress()) {
            return self::renderAjax( $view, $data );
        } else {
            self::renderFile( $view, $data, $responseCode);
        }
    }

    /**
     * Render the views and includes no oop functions for easy access by the template files.
     * if no view is provided, then it will echo the data sent through within the body.
     * @param $view
     * @param array $data
     * @param $responseCode
     * @throws ViewException
     */
    private static function renderFile( $view = null, $data = array(), $responseCode = 200 ) {

        // if using dot convention.
        if (strpos($view, '.')) {
            $view = str_replace('.', '/', $view);
        }

        if (is_null($view) || file_exists(VIEWS_PATH . $view . '.php')) {
            http_response_code($responseCode);
            self::init();
            self::includeView( $view, $data );
            exit;
        } else {
            if (strpos($view, '.php') > 0 ) {
                throw new ViewException('A view name cannot have a .php extension when called with View::render');
            } else {
                throw new ViewException('Calling view, but view does not exist. ' . VIEWS_PATH . $view . '.php');
            }
        }
    }

    /**
     * @param $view
     * @param array $data
     * @return mixed
     * @throws ViewException
     * @throws \Exception
     */
    private static function renderAjax($view, $data = array()) {

        // if using dot convention.
        if (strpos($view, '.')) {
            $view = str_replace('.', '/', $view);
        }

        if ( self::includeView($view, $data, true) ) {
            try {
                $file = STORAGE_PATH . 'views/ajax-' . str_replace('/', '.', $view);
                ob_start();
                self::init();

                //human keys in array become variables, $data still available
                if (!is_null($data) && is_array($data)) {
                    extract($data);
                }

                include $file;
                $result = ob_get_clean();
                return $result;
                //if an error occurred, clear output buffer and throw exception.
            } catch (\Exception $e) {
                ob_end_clean();
                throw $e;
            }
        } else {
            throw new ViewException('Could not return view');
        }
    }

    /**
     * @return bool
     */
    public static function isLoggedIn()
    {
        $result = isset($_SESSION['user_logged_in']) ? TRUE : false;
        return $result;
    }

    /**
     * @param $role
     * @return bool
     */
    public static function hasRole($role)
    {
        return self::is_user_role($role);
    }


    /**
     * @param $key
     * @return mixed
     * @throws ViewException
     */
    public static function getUser($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        else throw new ViewException('Unexisting property');
    }

    /**
     * renders feedback
     * echo out the feedback messages (errors and success messages etc.),
     * they are in $_SESSION["feedback_positive"] and $_SESSION["feedback_negative"]
     */
    public static function renderFeedbackMessages() {
        require_once(CORE_PATH . 'view_helpers/feedback.php');

        // delete these messages (as they are not needed anymore and we want to avoid to show them twice
        Session::set('feedback_positive', null);
        Session::set('feedback_negative', null);
    }

    /**
     * helper method for negative feedback
     * @param $message
     */
    public static function error($message)
    {
        self::feedback('error', $message);
    }

    /**
     * helper method for positive feedback
     * @param $message
     */
    public static function info($message)
    {
        self::feedback('success', $message);
    }

    /**
     * @param string $type
     * @param string $message
     */
    public static function feedback($type = 'notice', $message = 'Access Denied')
    {
        if ($type === 'notice' || $type === 'warning' || $type === 'error') {
            $_SESSION['feedback_negative'][] = $message;
        }
        if ($type === 'success') {
            $_SESSION['feedback_positive'][] = $message;
        }
    }
}
