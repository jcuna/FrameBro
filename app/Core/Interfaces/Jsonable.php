<?php
/**
 * Author: Jon Garcia
 * Date: 8/25/16
 * Time: 10:59 AM
 */

namespace App\Core\Interfaces;


interface Jsonable
{
    /**
     * Convert object into a json object
     * 
     * @return string
     */
    public function toJson();

}