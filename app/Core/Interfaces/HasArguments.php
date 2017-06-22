<?php
/**
 * Author: Jon Garcia.
 * Date: 6/6/17
 * Time: 7:08 PM
 */

namespace App\Core\Interfaces;


use App\Core\Arguments;

interface HasArguments
{
    public function isEmpty(): bool;

    public function __set(string $name, $value);

    public function __get(string $name);

    public function get(string $name);

    public function arguments(): Arguments;

}