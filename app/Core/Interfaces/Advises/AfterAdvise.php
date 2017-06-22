<?php
/**
 * Author: Jon Garcia.
 * Date: 2/16/17
 * Time: 9:54 PM
 */

namespace App\Core\Interfaces\Advises;

use App\Core\Request;

interface AfterAdvise
{
    public function exitHandler(&$output, Request $request);
}