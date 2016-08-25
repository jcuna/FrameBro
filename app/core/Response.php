<?php
/**
 * Author: Jon Garcia
 * Date: 8/25/16
 * Time: 11:24 AM
 */

namespace App\Core;


use App\Core\Api\AbstractResponse;

class Response extends AbstractResponse
{
    public static function render($data, $responseCode = 200)
    {
        self::setResponseCode($responseCode);
        self::setContent($data);
        parent::render();
        
    }

}