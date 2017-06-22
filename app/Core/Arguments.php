<?php
/**
 * Author: Jon Garcia
 * Date: 1/23/16
 * Time: 12:00 PM
 */

namespace App\Core;

/**
 * Class Request
 * @package App\Core\Http
 */
class Arguments implements \IteratorAggregate
{
    public function __construct(array $args = [])
    {
        foreach ($args as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this);
    }

    public function __get($name)
    {
        return $this->{$name} ?? null;
    }

}