<?php
/**
 * Created by PhpStorm.
 * Author: Jon Garcia
 * Date: 1/18/16
 */

namespace App\Models;

use App\Core\Model\Loupe;

class Role extends Loupe
{
    function users()
    {
        return $this->hasManyThrough('\\App\\Models\\User', '\\App\\Models\\RolesUsers', 'user_id', 'role_id' );
    }

}