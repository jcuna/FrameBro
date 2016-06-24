<?php
WebForm::forModel('\\App\\Models\\User');
Markup::before('div', 'well');
{
    Markup::element('h2', null, 'Login');
}
    Markup::after();
WebForm::open('login', null, '/login', 'col-md-7 col-md-offset-2', 'false', 'POST');
{
    WebForm::before();
    {
        WebForm::field('username', 'text', ['placeholder' => 'Username']);
    }
    WebForm::after();
    WebForm::before();
    {
        WebForm::field('password', 'password', ['placeholder' => 'Password']);
    }
    WebForm::after();
    WebForm::before();
    {
        WebForm::submit();
    }
    WebForm::after();

}
WebForm::close();
?>