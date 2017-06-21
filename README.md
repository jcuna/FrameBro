# FrameBro
_A Framework that's got bros' backs._

Author: **Jon Garcia**

Email: **garciajon@me.com**

**MVC Object Oriented framework.**

_Fast and lightweight without unnecessary stuff._

Full with Native Ajax support, Active Record Models and DB Migrations. Routes, Validation, Requests, Views, Controller, Markup, WebForm, Sessions, Authentication.

Embracing CoC and DRY principles.

## Native Ajax support:
You can write an entire application using FrameBro's native support for ajax.
The best part is, you would never have to write any javascript. All you'd have to do is 
tell our Ajax Request provider class that you want to register an ajax call.
For example, you can just declare a wrapper, a selector and a callback which are the minimum required params to make an ajax call.

~~~~~~~~~~~~~~~~~~~~~
ajaxRequest([
	'callback'      => 'controllerMethod',
	'selector'      => '#selector',
	'wrapper'       => '#div-wrapper',
	'jQueryMethod'  => 'html' //- optional, default is replaceWith
]);
~~~~~~~~~~~~~~~~~~~~~

You can write code such as the above in your controller and then return a view and Framebro will automaticaly take care of the rest.
You can also indicate a jsCallback to handle the request in the front end and instead of returning a View you can return an array or javascript object.

You can use our globally available ajaxRequest function which is a wrapper for AjaxRequest::ajaxQueue


## Active Record Models and DB Migrations:
Ruby developers love Ruby on Rails' take on Active Record, we implement an Active Record pattern similar to Ruby On Rails.

i.e
~~~~~~~~~~~~~~~~~~~~~
$model->find(25);
$model->where('column', 'value')->get();
$model->all();
$model->save();
~~~~~~~~~~~~~~~~~~~~~

Migrations are just as easy using our Migrations Class.
i.e
~~~~~~~~~~
public function up()
{
    $this->create('users', function(Table $t) {

        $t->incremental('uid')->unsigned();
        $t->char('username', 64)->index();
        $t->char('password');
        $t->string('fname', 64);
        $t->string('lname', 64);
        $t->string('login_token', 64)->null();
        $t->foreign('roles')->onDelete("no action"); // this will create a column role_id and add index by default.
        $t->timestamps();
    });

    $this->update('users', function(Table $t) {

        $t->string('email', 64)->unique();
        $t->change()->string('login_token', 64)->index();

    });
}

public function down() {
    $this->drop('users');
}
~~~~~~~~~~

## Routes:

Routes allow your application to have simple understandable urls.
FrameBro makes it simple to declare urls.

i.e.
~~~~~~~~~~~~~~~~~
Routes::get('/', 'home@index'); // home page uses the home controller and a method called index
Routes::post('users/login', 'users@login'); allows post request to users/login. uses users controller and login method
Routes::get('users/{username}', 'users@index'); // where {username} represents a string - to match numbers use {id}
//Example with closure
Routes::get('testing/{name}', function($name) {
    echo ("hey there $name");
});
~~~~~~~~~~~~~~~~~
You can handle all sorts of routes from simplest to most complex.

## Request:
HTTP arguments have a class Params that gather all sorts of arguments and gives you easy access to data sent as arguments via HTTP Methods.
i.e

~~~~~~~~~~~~~~~~~~~~~~~
$params = new Params();
$id = $params->id
~~~~~~~~~~~~~~~~~~~~~~~

$id is now an argument sent via a POST or a GET request. If data sent is JSON data, it gets decoded automatically.

Just as easy you can access all sorts of server, origin and header information.

## Validations:
You need to validate HTTP arguments before storing them or doing some logic to it. 
Validations make this easy. Your controller can validate this data without a hassle.

i.e
~~~~~~~~~~~~~~~~~~~~~
$params = new Params();

$this->validate($params, [
    'truck_id'        => ['required', 'numeric'],
    'start_date'      => ['required', 'date'],
    'hidden'          => ['boolean'],
    'vin'             => ['regex:customregex'],
    'plate'           => ['unique:table']
]);
~~~~~~~~~~~~~~~~~~~~~

There're tons of other validation methods that can be applied.

## Views:
It's easy to render your views, even if you're working with ajax.

i.e.
~~~~~~~~~~~~~~~~~~~~~
$roles = (new Role())->all()->toArray();
return View::render('user/create', ['roles' => $roles]);
~~~~~~~~~~~~~~~~~~~~~

Your template file now has a variable called $roles.

There're tons of other stuff you can do with views.

## Controllers:
A controller is the center piece of your MVC application. It instantiates models, gets their data ready and then dispatches it to the view.
Our controllers have a handful of helpful methods to allow you to concentrate on what makes your app great.
#### Some of the features include:
- Before filters.
    * Allows you to process request before it touches your controller.
- Before method filters.
    * Allows you to have a custom method handle what happens before a request hits your controller.
- Integration with our user authentication service.
and more.


## Markup:
A markup class that allows you to generate html without having to write html. Can be used in views to easily generate dynamic pages.

## Webform:
A Webform class for creating forms. It takes care of posted data, and add the values back to your fields. 
It integrates with your Models and identify which model the form belongs to.
This is useful to pre-populate fields such as when editing an existing record.

## Sessions:
A Session class to help you handle session data.

## Authentication:
You don't need to worry about crating authentication for your app. You can just use the built in authentication service and modify it to your liking.

### Some of the other goodies that are worth mentioning:
- Caching service
- Event listeners
- Pagination
- A status page after app is setup which tells you steps you need to take to get app ready.
- Admin page with information about errors, declared routes, phpinfo, users, etc.
- Cli integration
And more... much more.