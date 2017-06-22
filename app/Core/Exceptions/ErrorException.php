<?php
/**
 * Created by PhpStorm.
 * User: jcuna
 * Date: 5/29/16
 * Time: 3:13 PM
 */

namespace App\Core\Exceptions;


class ErrorException extends \Exception {

    /**
     * @var \Exception
     */
    protected $severity;

    /**
     * errorException constructor.
     * @param string $message
     * @param int $code
     * @param \Exception $severity
     * @param $filename
     * @param $lineNo
     */
    public function __construct($message, $code, $severity, $filename, $lineNo) {
        $this->message = $message;
        $this->code = $code;
        $this->severity = $severity;
        $this->file = $filename;
        $this->line = $lineNo;
    }

    /**
     * get severity code
     *
     * @return \Exception
     */
    public function getSeverity() {
        return $this->severity;
    }

    /**
     * Get severity type
     *
     * @return string
     */
    public function getSeverityType()
    {
        $severity = $this->getSeverity();

        switch ($severity) {
            case E_USER_ERROR:
                $type = 'Fatal Error';
                break;
            case E_USER_WARNING:
            case E_WARNING:
                $type = 'Warning';
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
            case @E_STRICT:
                $type = 'Notice';
                break;
            case @E_RECOVERABLE_ERROR:
                $type = 'Catchable';
                break;
            default:
                $type = 'Unknown Error';
                break;
        }

        return $type;
    }
}