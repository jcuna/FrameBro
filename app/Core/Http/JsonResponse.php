<?php
/**
 * Author: Jon Garcia
 * Date: 2/13/16
 * Time: 7:28 PM
 */

namespace App\Core\Http;
use App\Core\Interfaces\AbstractResponse;

/**
 * Class JsonResponse
 * @package App\Core
 */
class JsonResponse extends AbstractResponse
{
    /**
     * @param $data
     * @param int $status
     * @param array|null $extra
     */
    public static function Response($data, $status = 200, array $extra = null) {

        $responseArray = ['data' => $data, 'status' => $status];

        if (!is_null($extra) && !empty($extra)) {
            $key = key($extra);
            $val = $extra[$key];
            $responseArray[$key] = $val;
        }

        self::setHeader('Content-type', 'application/json');
        self::setResponseCode($status);
        self::render($responseArray);
    }
}