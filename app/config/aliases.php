<?php
/**
  |--------------------------------------------------------|
  | This is how we can access these classes from a view    |
  | without having to specify the namespace.               |
  | This only works for static methods                     |
  |--------------------------------------------------------|
 */
return [
    'View' => \App\Core\Http\View::class,
    'WebForm' => \App\Core\Html\WebForm::class,
    'Webform' => \App\Core\Html\WebForm::class,
    'Markup' => \App\Core\Html\Markup::class,
    "Router" => \App\Core\Http\Routing\Router::class
];
