<?php
/**
 * Author: Jon Garcia.
 * Date: 4/9/16
 * Time: 11:56 PM
 */

namespace App\Models;


use App\Core\Model\Loupe;

class Operator extends Loupe
{
    protected $customTime = true;

    public function trucks() {

        return $this->belongsTo('App\Models\Truck', 'operator_id');
    }

}