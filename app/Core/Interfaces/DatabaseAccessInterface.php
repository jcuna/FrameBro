<?php
/**
 * Created by PhpStorm.
 * User: jcuna
 * Date: 5/30/16
 * Time: 9:41 AM
 */

namespace App\Core\Interfaces;


use App\Core\Db\Statement;

interface DatabaseAccessInterface
{

    /**
     * @return Statement
     */
    public function getStatement();

    /**
     * @return \PDO
     */
    public function getConnection();

}