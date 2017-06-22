<?php

/**
 |-----------------------------------------------------
 | global functions
 |-----------------------------------------------------
 | This file has functions that are available globally
 |
 */

if (! function_exists("getDirectoryFiles")) {
    /**
     * get all files in a directory
     * @param $dir
     * @return array
     */

    function getDirectoryFiles($dir)
    {

        return \App\Core\Http\Controller::getDirectoryFiles($dir);
    }
}
if (! function_exists("shiftElement")) {
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
}

if (! function_exists("ajaxRequest")) {
    /**
     * Set up an ajax request call with our ajax request provider.
     *
     * @param array $data
     */
    function ajaxRequest(array $data)
    {
        \App\Core\Ajax\AjaxRequest::ajaxQueue($data);
    }
}

if (! function_exists("jQueryMethodOverride")) {
    /**
     * Sends a message to override current jQuery method
     *
     * @param string $jQueryMethodName
     */
    function jQueryMethodOverride($jQueryMethodName = "replaceWith")
    {
        \App\Core\Ajax\AjaxRequest::jQueryMethod($jQueryMethodName);
    }
}

if (! function_exists("getExecutionTime")) {
    /**
     * Calculates execution time
     *
     * @param $start
     * @return mixed
     */
    function getExecutionTime(int $start)
    {
        return (microtime(true) - $start);
    }
}

if (! function_exists("sqlTime")) {
    /**
     * Converts a date field into sql datetime.
     *
     * @param string $date
     * @return string
     */
    function sqlTime(string $date)
    {
        $objDate = new \DateTime($date);

        return $objDate->format("Y-m-d H:i:s");
    }
}

if (! function_exists("log_exception")) {
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
}

if (! function_exists("dd")) {
    Kint::$aliases[] = 'dd';
    function dd() {
        array_map(function ($dumped_val) {
            d($dumped_val);
        }, func_get_args());

        die(1);
    }
}

if (! function_exists("sd")) {
    Kint::$aliases[] = 'sd';
    function sd() {
        array_map(function ($dumped_val) {
            s($dumped_val);
        }, func_get_args());

        die(1);
    }
}