<?php
/**
 * Author: Jon Garcia
 * Date: 3/23/16
 * Time: 11:21 AM
 */

namespace App\Models;


use App\Core\Model\Loupe;

class Task extends Loupe
{
    protected $primaryKey = 'task_id';

    public function truck()
    {
        return $this->hasOne('\App\Models\Truck');
    }

    public function load()
    {
        return $this->hasOne('\App\Models\Load');
    }

}