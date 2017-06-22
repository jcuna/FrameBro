<?php
/**
 * Author: Jon Garcia.
 * Date: 2/18/17
 * Time: 12:51 PM
 */

namespace App\Core;

use App;

class ExceptionHandler
{
    /**
     * @var \Exception
     */
    private $exception;

    public function __construct(\Throwable $exception)
    {
        $this->exception = $exception;
        $this->report();
    }

    private function report()
    {
        if (App::app()->isReportingErrors()) {
            if (App::hasExceptionHandler()) {
                App::getExceptionHandler()->report($this->exception);
            } else {
                App::dd($this->exception);
            }
        }
    }
}