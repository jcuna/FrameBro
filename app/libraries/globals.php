<?php

/**
 * This file has functions that are available globally
 */

//vendor autoloading
require ABSOLUTE_PATH . '/vendor/autoload.php';

/** Start dotenv instance */
if (file_exists(ABSOLUTE_PATH . '/.env')) {
    $dotenv = new Dotenv\Dotenv( ABSOLUTE_PATH );
    $dotenv->load();
}

/**
 * @overrides ddd by Kint in case there's an ajax call.
 *
 * @param $dumpData
 */
function ddd() {

    $args = func_get_args()[0];

    if (count($args) === 1) {
        $args = $args[0];
    }

    //if there's an ajax call in progress, let's return the output
    if (\App\Core\Ajax\AjaxController::ajaxCallInProgress()) {

        ob_start();

        Kint::dump($args);

        $output = ob_get_clean();

        \App\Core\JsonResponse::Response( $output, 200 );
    }
    else {
        die(Kint::dump($args));
    }
}

/**
 * @param $dumpData
 */
function dd() {
    $args = func_get_args();
    ddd($args);
}

/**
 * get all files in a directory
 * @param $dir
 * @return array
 */
function getDirectoryFiles($dir) {

    return \App\Core\Http\Controller::getDirectoryFiles($dir);
}

/**
 * @param $errNo
 * @param $errStr
 * @param $errFile
 * @param $errLine
 * @throws errorException
 */
function exception_error_handler($errNo, $errStr, $errFile, $errLine ) {

    throw new \App\Core\Exceptions\ErrorException($errStr, 0, $errNo, $errFile, $errLine);

}

/**
 * move array elements from to
 *
 * @param $array
 * @param $oldPos
 * @param $pos
 */
function shiftElement(&$array, $oldPos, $pos)
{
    $out = array_splice($array, $oldPos, 1);
    array_splice($array, $pos, 0, $out);
}

/**
 * Set up an ajax request call with our ajax request provider.
 * 
 * @param array $data
 */
function ajaxRequest(array $data)
{
    \App\Core\Ajax\AjaxRequest::ajaxQueue($data);
}

/**
 * Sends a message to override current jQuery method
 *
 * @param string $jQueryMethodName
 */
function jQueryMethodOverride($jQueryMethodName = "replaceWith")
{
    \App\Core\Ajax\AjaxRequest::jQueryMethod($jQueryMethodName);
}

/**
 * Calculates execution time
 *
 * @param $start
 * @return mixed
 */
function getExecutionTime($start)
{
    return (microtime(true) - $start) * 1000;
}

/**
 * Converts a date field into sql datetime.
 *
 * @param $date
 * @return string
 */
function sqlTime($date)
{
    $objDate = new \DateTime($this->{$date});

    return $objDate->format("Y-m-d H:i:s");
}

/**
 * Logs an exception to the error log
 *
 * @param Exception $e
 */
function log_exception(\Exception $e)
{
    $severity = 'exception';
    if ($e instanceof \App\Core\Exceptions\ErrorException) {
        $severity = $e->getSeverityType();
    }

    $message = "$severity ";
    $message .= get_class($e);
    $message .= " with message " . $e->getMessage();
    $message .= " in " . $e->getFile();
    $message .= ":" . $e->getLine();

    error_log($message, 0);
}