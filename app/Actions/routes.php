<?php
/**
 * Created By: Jon Garcia
 * Date: 1/16/16
 * Routes file.
 */

/**
 |--------------------------------------------
 | routes.php
 |--------------------------------------------
 | Declare your routes here. Look at the various working examples here.
 | Feel free to modify it as you build your application.
 */
namespace App\Core\Http\Routing {

    use App\Core\Http\View;
    use \App\Services\IsAuthenticated;

    Router::all('login', 'Users@login', ['via' => 'login_path']);

    Router::group(["before" => IsAuthenticated::class], function () {
        Router::get('/', 'Home@index');
        Router::get('users/logout', 'Users@logout');
        Router::all('users/create', 'Users@create');
        Router::all('users', 'Users@index');
        Router::get('users/all', 'Users@allUsers');
        Router::get('users/{username}', 'Users@index');
        Router::post('users/login', 'Users@login');
        Router::all('users/deleteCurrentUser', 'Users@deleteCurrentUser');
    });
//Example with closure
    Router::get('testing/{name}', function ($name) {
        echo("hey there $name");
    });


    Router::group(["before" => IsAuthenticated::class], function () {
        // Example declaring multiple actions for a controller.
        // This is also a useful admin menu that gives you info about the system and allows you to create the first user
        Router::resources('admin', 'Admin',
            [
                ['get' =>
                    ['index', 'showRoutes', 'logs', 'info', 'statusReport', 'memcachedStats', 'firstUser']
                ],
                ['post' =>
                    ['index', 'firstUser']
                ]
            ]
        );
    });

//When a page is missing.
    Router::missing(function () {
        return View::render('errors/error', "The requested page doesn't exist", 404);
    });
}