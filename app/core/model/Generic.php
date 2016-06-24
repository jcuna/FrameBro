<?php
/**
 * Author: Jon Garcia
 * Date: 1/24/16
 */

namespace App\Core\Model;
/**
 * This class can query the database without having to create a new class model file.
 * Pass the table name to the instance of the class and then use it like you would any model that extends Loupe.
 * There're several limitations to this so it's better left for specific scenario where minimum db interaction is
 * needed.
 *
 * Class Generic
 * @package App\Core\Model
 */
Class Generic extends Loupe
{
    /**
     * Sets the table name sent as constructor.
     *
     * @var string
     */
    protected $table;

    /**
     * Generic constructor.
     * @param $table
     */
    public function __construct($table) {
        $this->table = $table;
        parent::__construct();
    }
}