<?php
/**
 * Author: Jon Garcia.
 * Date: 4/6/16
 * Time: 9:59 PM
 */

namespace App\Core\Exceptions;

use App\Core\Interfaces\BroExceptionsInterface;

class ControllerException extends \Exception implements BroExceptionsInterface
{

}