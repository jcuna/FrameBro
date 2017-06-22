<?php
/**
 * Author: Jon Garcia
 * Date: 3/17/16
 * Time: 9:43 AM
 */

namespace App\Core\Exceptions;

use App\Core\Interfaces\BroExceptionsInterface;

class ModelException extends \Exception implements BroExceptionsInterface
{

    public function __construct($message = "", $code = 0, $file = null, $line = null)
    {
        parent::__construct($message, $code);
        if ($file) {
            $this->file = $file;
        }
        if ($line) {
            $this->line = $line;
        }
    }
}