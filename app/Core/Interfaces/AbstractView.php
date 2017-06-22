<?php
/**
 * Author: Jon Garcia
 * Date: 3/17/16
 * Time: 5:07 PM
 */

namespace App\Core\Interfaces;

use App\Core\Ajax\AjaxController;
use App\Core\Ajax\AjaxRequest;
use App\Core\Exceptions\ViewException;
use App\Core\Interpreter;

/**
 * Class AbstractView
 * @package App\Core\Api
 */
abstract class AbstractView
{

    /**
     * @var string
     */
    private static $masterLayout = 'master';

    /**
     * @var string
     */
    private static $currentLayout;

    /**
     * Directing non existing static methods to the late binding of this
     * abstract class which in most cases will be the view
     * @param $name
     * @param $arguments
     * @throws ViewException
     */
    public static function __callStatic($name, $arguments)
    {
        $static = new static;
        if (method_exists($static, $name)) {
            call_user_func_array('static::' . $name, $arguments);
        } else {
            throw new ViewException("Method $name does not exist");
        }
    }

    /**
     * get layout file
     *
     * @return string
     */
    private static function getLayout()
    {
        if (isset(self::$currentLayout)) {
            $layoutName = self::$currentLayout.'.php';
        } else {
            $layoutName = self::$masterLayout.'.php';
        }

        return VIEWS_PATH . 'layouts/'.$layoutName;
    }

    /**
     * @param $layoutName
     * @throws ViewException
     */
    public static function setMasterLayout($layoutName)
    {
        self::$masterLayout = $layoutName;
        if (!file_exists(self::getLayout())) {
            throw new ViewException(self::getLayout().' is not a valid layout file. Please verify file exists');
        }
    }

    /**
     * current layout can only be used once, use master layout to permanently use the same template.
     *
     * @param $layoutName
     * @throws ViewException
     */
    public static function setCurrentLayout($layoutName)
    {
        self::$currentLayout = $layoutName;
        if (!file_exists(self::getLayout())) {
            throw new ViewException(self::getLayout().' is not a valid layout file. Please verify file exists');
        }
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Parses view template and partials. Makes use of the xattr extended attributes library *
     * to parse partials when they have been updated and not their parent view.              *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param $view
     * @param null $data
     * @param bool $ajax
     * @return bool
     * @throws ViewException
     */
    final protected static function includeView($view, $data = null, $ajax = false)
    {
        /** @var $partial | used to tell weather we're rending a view or a template */
        static $partial = false;
        /** @var $partialProperties | stores the properties of a partial when in a partial rendering. */
        static $partialProperties = array();
        /** @var $parentView | stores the name of a partial's parent view when rendering a partial */
        static $parentView;

        //human keys in array become variables, $data still available
        if (!$ajax && !is_null($data) && is_array($data)) {
            extract($data);
        }

        static::statCache();

        if (!is_null($view)) {
            /** view file is the cached processed version of the view stored in storage dir
             * if is ajax view prepend text to viewFile name
             */
            if ($ajax) {
                $viewFile = STORAGE_PATH . 'views/ajax-' . str_replace('/', '.', $view);
            } else { $viewFile = STORAGE_PATH . 'views/' . str_replace('/', '.', $view); }

            /**  templateView is the original view file without being processed. */
            $templateView = VIEWS_PATH . $view . '.php';

            $masterTemplate = self::getLayout();
            self::$currentLayout = null;

            $viewExists = file_exists($viewFile);

            /** Do we support extended attributes? */
            if (getenv('XATTR_ENABLED') && $viewExists) {
                /**  get array partials form extended attributes */
                $arrPartials = json_decode(xattr_get($viewFile, 'partials'), true); //returns false if no extended attributes
                if ($arrPartials && key($arrPartials) === $viewFile) {

                    /** @var  $partialProperties | tricky!
                    -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -*
                    -*    if we're rendering a partial, then we want the attributes untouched. If we're not rendering  -*
                    -*       a partial but it's parent view, and partials are up to date, we don't want to re-ren      -*
                    -*         der but we want to copy the attributes over to the new parent view that we're           -*
                    -*                                            rendering.                                           -*
                    -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -* -*
                     */
                    $partialProperties = $partial ? $partialProperties : $arrPartials;
                    foreach ($arrPartials[$viewFile] as $p) {
                        if (filemtime(VIEWS_PATH . $p['templateView'] . '.php') > filemtime($p['viewFile'])) {
                            $partial = true;
                            static::includeView($p['templateView']);
                        }
                    }
                }
            }

            if (!$viewExists || (filemtime($templateView) > filemtime($viewFile))
                || (filemtime($masterTemplate) > filemtime($viewFile))) {
                if (!file_exists(STORAGE_PATH . 'views')) {
                    try {
                        mkdir(STORAGE_PATH . 'views');
                    } catch (\Throwable $exception) {
                        throw new ViewException('Failed creating directory ' . STORAGE_PATH .
                            'views, make sure the web server has permission to do so.');
                    }
                }

                $tmpView = file_get_contents($templateView);

                if (Interpreter::hasPartials($tmpView)) {
                    foreach (Interpreter::getPartials() as $file) {
                        $parentView = $viewFile;
                        $partial = true;
                        //recursive call to render the partial
                        static::includeView($file);
                    }
                }

                /**
                 * if is ajax view make partial true so that it only parses the view and returns true;
                 */
                if ($ajax) {
                    $partial = true;
                }

                $newFile = Interpreter::parseView($tmpView, $ajax);

                /** we'd only get here if it's a partial, so we return as this should not be included. */
                if ($partial) {
                    if (!file_put_contents($viewFile, $newFile )) {
                        throw new ViewException('Failed creating directory ' . $viewFile .
                            $newFile . ', make sure the web server has permission to do so.');
                    }
                    chmod(STORAGE_PATH . 'views', 0777);
                    chmod($viewFile, 0777);
                    $partialProperties[$parentView][] = [ 'viewFile' => $viewFile, 'templateView' => $view, ];
                    $partial = false;
                    $parentView = null;
                    return true;
                }

                $master = file_get_contents($masterTemplate);
                $fileToRender = Interpreter::parseLayout($master, $newFile);

                if (!file_put_contents($viewFile, $fileToRender)) {
                    throw new ViewException('Failed creating file ' . $viewFile .
                        ', make sure the web server has permission to do so.');
                }
                chmod(STORAGE_PATH . 'views', 0777);
                chmod($viewFile, 0777);
                /** can we write extended attributes? */
                if (getenv('XATTR_ENABLED') && !empty($partialProperties)) {
                    $xattrValue = json_encode($partialProperties);
                    xattr_set($viewFile, 'partials', $xattrValue);
                }
            } elseif ($partial) {
                // let's make sure we reset $partial so that we don't mistakenly process a view template as a partial $partial */
                $partial = false;
                // @return | to calling function */
                return true;
            }
            /**
             * don't include file if ajax is true;
             */
            if ($ajax) {
                return true;
            } else {
                include $viewFile;
            }
        } else {
            echo $data;
        }
    }


    /**
     * @param $location string
     */
    final public static function AjaxRedirect($location)
    {
        AjaxController::$redirect = $location;
    }

    /**
     * clears php statcache @link http://php.net/manual/en/function.clearstatcache.php
     */
    final private static function statCache() {
        if (getenv('ENV') == 'dev') {
            clearstatcache();
        }
    }

    /**
     * @param $role
     * @return bool
     */
    final public static function is_user_role($role)
    {
        $result = false;

        if (isset($_SESSION['roles'])) {

            if (is_array($role)) {
                $result = !empty(array_intersect($role, $_SESSION['roles']));
            } elseif (in_array($role, $_SESSION['roles'])) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Adds the specified js file within the @path variable to the specified uri
     * @param $path
     */
    public static function add_js($path) {
        echo $script = '<script src="' . $path .  '"></script>' . "\n";
    }
    /**
     * Adds the specified css file within the @path variable to the specified uri
     * @param $path
     */
    public static function add_css($path) {
        echo '<link rel="stylesheet" href="' .$path. '">' . "\n";
    }
}