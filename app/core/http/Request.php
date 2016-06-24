<?php
/**
 * Author: Jon Garcia
 * Date: 1/23/16
 * Time: 12:00 PM
 */

namespace App\Core\Http;

/**
 * Class Request
 * @package App\Core\Http
 */
class Request implements \IteratorAggregate
{

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {

        return new \ArrayIterator($this);

    }

}