<?php
/**
 * Author: Jon Garcia
 * Date: 3/17/16
 * Time: 9:30 AM
 */

namespace App\Core\Exceptions;

use App\Core\Api\BroExceptionsInterface;

class AppException extends \Exception implements BroExceptionsInterface
{

}