<?php
/**
 * Created By: Jon Garcia
 * Date: 1/16/16
 * Routes file.
 */

/**
 * Declare your routes here. Look at the various working examples here.
 * Feel free to modify it as you build your application.
 */

Routes::get('/', 'home@index');

Routes::all('login', 'users@login', ['via' => 'login_path']);
Routes::get('users/logout', 'users@logout');
Routes::all('users/create', 'users@create');
Routes::all('users', 'users@index');
Routes::get('users/all', 'users@allUsers');
Routes::get('users/{username}', 'users@index');
Routes::post('users/login', 'users@login');
Routes::all('users/deleteCurrentUser', 'users@deleteCurrentUser');

//Example with closure
Routes::get('testing/{name}', function($name) {
    echo ("hey there $name");
});

// Example declaring multiple actions for a controller.
// This is also a useful admin menu that gives you info about the system and allows you to create the first user
Routes::resources('admin', 'admin',
    [
        [ 'get' =>
            ['index', 'showRoutes', 'logs', 'info', 'statusReport', 'memcachedStats', 'firstUser']
        ],
        ['post' =>
            ['index', 'firstUser']
        ]
    ]
);

//When a page is missing.
Routes::missing( function()
{
    return View::render('errors/error', 'The requested page doesn\'t exist', 404);
});
