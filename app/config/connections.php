<?php

/**
 * This is where we configure the different database connections.
 *
 * By default default connection will be used.
 */

return [

    //sets the default connection.
    'default' => 'dev',

    'dev' => [
        'db_type'   => getenv('DB_TYPE'),
        'db_host'   => getenv('DB_HOST'),
        'db_name'   => getenv('DB_NAME'),
        'charset'   => 'utf8'
    ],

    'prod' => [
        'db_type'   => getenv('DB_TYPE_PROD'),
        'db_host'   => getenv('DB_HOST_PROD'),
        'db_name'   => getenv('DB_NAME_PROD'),
        'charset'   => 'utf8'
    ],

];