<?php
/**
 * Author: Jon Garcia.
 * Date: 2/16/17
 * Time: 10:10 PM
 */

namespace App\Services;


use App\Controllers\homeController;
use App\Core\EventReceiver;
use App\Core\Interfaces\HandleException;
use App\Core\Http\Session;

class BootstrapService
{
    public static function boot(\App $app)
    {

        //Register any initialization services for your application.
        //i.e and event listener or application hooks

//        $app->registerHandler(new class() implements HandleException {
//            public function report(\Exception $e) {
//                dd("Hello from the other side bitch");
//            }
//        }, "exception");

//        $app->registerHandler(Test::class, "exception");
    }

}
