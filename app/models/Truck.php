<?php
/**
 * Author: Jon Garcia
 * Date: 3/23/16
 * Time: 11:21 AM
 */

namespace App\Models;


use App\Core\Model\Loupe;

class Truck extends Loupe
{
    protected $customTime = true;

    public function operator()
    {
        return $this->hasOne('\App\Models\Operator', 'oid');
    }

}