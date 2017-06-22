<?php
/**
 * Author: Jon Garcia
 * Date: 3/17/16
 * Time: 9:30 AM
 */

namespace App\Core\Exceptions;

use App\Core\Interfaces\BroExceptionsInterface;
use Exception;

/**
 * Class AppException
 * @package App\Core\Exceptions
 */
class AppException extends Exception implements BroExceptionsInterface
{
    /**
     * AppException constructor.
     * @param string $message
     * @param string $file
     * @param string $line
     * @param int $code
     */
    public function __construct($message = "", $file = "", $line = "", $code = 0)
    {
        $this->file = $file;
        $this->line = $line;
        parent::__construct($message, $code);
    }
}