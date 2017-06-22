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
        'db_type'   => App::env('DB_TYPE', 'msql'),
        'db_host'   => App::env('DB_HOST', 'localhost'),
        'db_name'   => App::env('DB_NAME', 'test'),
        'db_port'   => App::env('DB_PORT', '3306'),
        'charset'   => 'utf8'
    ],

    'prod' => [
        'db_type'   => App::env('DB_TYPE_PROD', 'msql'),
        'db_host'   => App::env('DB_HOST_PROD', 'localhost'),
        'db_name'   => App::env('DB_NAME_PROD', 'test'),
        'db_port'   => App::env('DB_PORT_PROD', '3306'),
        'charset'   => 'utf8'
    ],

];