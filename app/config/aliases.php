<?php
/**
 * ---------------------------------------------------------
 * | This is how we can access these classes from a view    |
 * | without having to specify the namespace.               |
 * | This only works for static methods                     |
 * |--------------------------------------------------------|
 */
return array(
    'View' => 'App\Core\View',
    'WebForm' => 'App\Core\Html\WebForm',
    'Webform' => 'App\Core\Html\WebForm',
    'Markup' => 'App\Core\Html\Markup'
);
