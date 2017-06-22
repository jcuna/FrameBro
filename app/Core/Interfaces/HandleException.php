<?php
/**
 * Author: Jon Garcia.
 * Date: 2/18/17
 * Time: 1:03 PM
 */

namespace App\Core\Interfaces;


interface HandleException
{
    public function report(\Throwable $e);

}