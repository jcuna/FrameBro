<?php
/**
 * Author: Jon Garcia.
 * Date: 8/9/16
 * Time: 10:30 PM
 */

namespace App\Core\Api;


interface Arrayable
{
    /**
     * returns array representation of an object
     *
     * @return array
     */
    public function toArray();

}