<?php
/**
 * Created By: Jon Garcia
 * Date: 1/16/16
 **/
namespace App\Core;

use App\Core\Ajax\AjaxController;
use App\Core\Exceptions\AppException;
use App\Core\Http\Routes;
use App\Core\Api\BroExceptionsInterface;

/**
 * Class App
 * @package App\Core
 */
class App {

	private $caller;

	/**
	 * App constructor.
	 */
	public function __construct() {
		Session::init();

		//TODO move to a cron
		self::cleanLogFile();
        //sets some env variables
        self::SetXattrSupport();

		$this->caller = isset($_SERVER['SHELL']) ? 'CLI' : 'WEB';

        return $this->routeRequest();
	}

    /**
     * @return mixed
     * @throws AppException
     */
    private function routeRequest()
    {
        //if it's a web request not a cli request.
        if ($this->caller === 'WEB') {

            try {

                $routes = new Routes();

            } catch ( \Exception $e ) {
                log_exception($e);
                if ( getenv('ENV') === 'dev' || getenv('ENV') === false ) {

                    if (AjaxController::ajaxCallInProgress()) {

                        ddd([$e->getMessage(), debug_backtrace()]);
                    }
                    !+ddd([$e->getMessage(), debug_backtrace()]);
                }
                else {
                    echo 'An error has occurred';
                }
                exit;
            }

            if ($routes->parseRoutes()) {
                if ($routes->controller === 'callable') {
                    $routes->arguments = $routes->arUri ? array_values($routes->arUri) : array();
                    return call_user_func_array($routes->action, $routes->arguments);
                } elseif ($routes->validateRoutes()) {

                    return $this->fireApp($routes);

                } else {
                    throw new AppException('Your routes file could not be validated');
                }
            }
        }

        return $routes->callMissingPage();
    }



	/**
     * @param $routes Routes object type
	 *
	 * @param Routes $routes
	 * @return mixed
	 */
	private function fireApp(Routes $routes)
	{
		try {
            $routes->arguments = $routes->arUri ? array_values($routes->arUri) : array();
            $action = $routes->action . '{action}';
			return call_user_func_array( array( $routes->controller, $action) , $routes->arguments );

		} catch ( \Exception $e ) {
            log_exception($e);
            if ( getenv('ENV') === 'dev' || getenv('ENV') === false ) {

                $m = "Message: " . $e->getMessage();
                $l = "Line: " . $e->getLine();
                $f = "File: " . $e->getFile();

                +!dd($m, $l, $f, ['trace' => debug_backtrace()]);

            } else {
                echo 'An error has occurred';
            }
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
	private static function SetXattrSupport() {
        $supportsExtendedAttr = (int)extension_loaded('xattr');
		putenv("XATTR_SUPPORT=$supportsExtendedAttr");
        $osExtendedAttr = $supportsExtendedAttr ? (int)xattr_supported(FILES_PATH . 'README.txt') : 0;
		putenv("XATTR_ENABLED=$osExtendedAttr");
	}
}