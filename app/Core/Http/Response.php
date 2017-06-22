<?php
/**
 * Author: Jon Garcia
 * Date: 8/25/16
 * Time: 11:24 AM
 */

namespace App\Core\Http;

use App\Core\Interfaces\AbstractResponse;

class Response extends AbstractResponse
{
    /**
     *
     * @overwrite parent::render
     * @param null $content
     * @param null $responseCode
     */
    public static function render($content = null, $responseCode = null)
    {
        if (!is_null($responseCode)) {
            self::setResponseCode($responseCode);
        }
        if (!is_null($content)) {
            self::setContent($content);
        }
        parent::render();
    }
}