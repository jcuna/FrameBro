<?php
Markup::before('div', 'container');
{
    Markup::before('div', 'panel panel-default');
    {
        Markup::element('div', ['class' => 'panel-heading'], 'Error');
        Markup::element('div', ['class' => 'panel-body'], $data);
    }
    Markup::after();
}
Markup::after();
?>