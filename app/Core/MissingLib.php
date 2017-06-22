<?php
/**
 * Author: Jon Garcia
 * Date: 3/7/16
 * Time: 3:30 PM
 */

namespace App\Core;

/**
 * When system has dependencies and those dependencies are missing, this class returns false to all method calls
 * Careful to use this as it may make it almost impossible to debug your code. This should only be used when
 * the library does not break your application such is the case of an enhancement or feature or other specific
 * scenario. i.e. Memcached.
 * Class MissingLib
 * @package App\Core
 */
class MissingLib
{
    public $name;
    public $arguments = array();
    /**
     * @param $name
     * @param $arguments
     * @return bool
     */
    public function __call($name, $arguments)
    {
        $this->name = $name;
        $this->arguments = $arguments;
        return false;
    }

}