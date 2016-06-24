<?php
/**
 * Author: Jon Garcia
 * Date: 2/13/16
 * Time: 7:28 PM
 */

namespace App\Core;

/**
 * Class JsonResponse
 * @package App\Core
 */
class JsonResponse
{
    /**
     * @param $data
     * @param int $status
     * @param array|null $extra
     */
    public static function Response($data, $status = 200, array $extra = null) {

        $responseArray = ['data' => $data, 'status' => $status ];

        if (!is_null($extra) && !empty($extra)) {
            $key = key( $extra );
            $val = $extra[$key];
            $responseArray[$key] = $val;
        }

        http_response_code( $status );
        header('Content-type: application/json');
        echo json_encode( $responseArray );
        exit;
    }

}