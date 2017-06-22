<?php
/**
 * Author: Jon Garcia.
 * Date: 5/30/16
 * Time: 7:16 PM
 */

namespace App\Core\Migrations;

use App\Core\Db\Statement;
use App\Core\Libraries\Inflect;

class Table
{
    /**
     * The table name
     *
     * @var string
     */
    private $tableName;

    /**
     * @var Statement;
     */
    public $stm;

    /* mappings for certain reserved words

     * @var array
     */
    private $MapMethod = [
        'default' => 'defaultValue'
    ];

    /**
     * The collation
     *
     * @var string
     */
    private $collate = 'utf8_general_ci';

    /**
     * Weather we want to alter a table
     *
     * @var bool
     */
    private $alter = false;

    /**
     * Contains all renamed columns
     *
     * @var array
     */
    private $renamedColumns = [];

    /**
     * Table constructor.
     * @param $tableName
     * @param Statement $statement
     * @param bool $custom
     */
    public function __construct($tableName, Statement $statement, $custom = false)
    {
        $this->stm = $statement;

        $this->tableName = $tableName;

        if (!$custom) {
            $this->stm->create();
        }
    }

    /**
     * @param $collate
     */
    public function setCollate($collate)
    {
        $this->collate = $collate;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->tableName;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function string($name, $length = 600)
    {
        $type = $length <= '255' ? 'varchar' : 'text';

        $this->stm->addColumn($name, $type, $length);

        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function char($name, $length = 255)
    {
        $this->stm->addColumn($name, 'varchar', $length);

        return $this;

    }

    /**
     * @param string $id
     * @param int $length
     * @return $this
     */
    public function incremental($id = 'id', $length = 11)
    {
        $this->stm->addColumn($id, 'primary', $length);

        return $this;
    }

    /**
     * @return $this
     */
    public function timestamps()
    {
        $this->stm->addTimeStamps();

        return $this;
    }

    /**
     * @return $this
     */
    public function unsigned()
    {
        $this->stm->makeUnsigned();

        return $this;
    }

    /**
     * @param string $id
     * @param int $length
     * @return $this
     */
    public function bigIncremental($id = 'id', $length = 20)
    {
        $this->stm->addColumn($id, 'bigPrimary', $length);

        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function bigInteger($name, $length = 20)
    {
        $this->stm->addColumn($name, 'bigint', $length);

        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function smallInteger($name, $length = 3)
    {
        $this->stm->addColumn($name, 'tinyint', $length);

        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function integer($name, $length = 11)
    {
        $this->stm->addColumn($name, 'int', $length);

        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function mediumInteger($name, $length = 8)
    {
        $this->stm->addColumn($name, 'mediumint', $length);
        return $this;
    }

    /**
     * @param $name
     * @param int $length
     * @return $this
     */
    public function binary($name, $length = 255)
    {
        $this->stm->addColumn($name, 'binary', $length);

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function boolean($name)
    {
        $this->stm->addColumn($name, 'bool');

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function date($name)
    {
        $this->stm->addColumn($name, 'date');

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function dateTime($name)
    {
        $this->stm->addColumn($name, 'datetime');

        return $this;
    }

    public function timestamp($name)
    {
        $this->stm->addColumn($name, 'timestamp');

        return $this;

    }

    /**
     * @param $name
     * @param int $m
     * @param int $d
     * @return $this
     */
    public function decimal($name, $m = 5, $d = 2)
    {
        $length = "$m, $d";
        $this->stm->addColumn($name, 'decimal', $length);

        return $this;
    }

    /**
     * @param $name
     * @param int $m
     * @param int $d
     * @return $this
     */
    public function double($name, $m = 15, $d = 8)
    {
        $length = "$m, $d";
        $this->stm->addColumn($name, 'double', $length);

        return $this;
    }

    /**
     * @param $name
     * @param array $list
     * @return $this
     */
    public function enum($name, array $list)
    {
        $this->stm->addColumn($name, 'enum', $list);

        return $this;
    }

    /**
     * @param $name
     * @param int $m
     * @param int $d
     * @return $this
     */
    public function float($name, $m = 10, $d = 2)
    {
        $precision = "$m, $d";

        $this->stm->addColumn($name, 'float', $precision);

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function json($name)
    {
        $this->stm->addColumn($name, 'json');
        return $this;
    }

    /**
     * @param $name
     */
    public function serial($name)
    {
        $this->stm->addColumn($name, 'serial');
    }

    /**
     * @param $name
     * @return $this
     */
    public function longText($name)
    {
        $this->stm->addColumn($name, 'longtext');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function text($name)
    {
        $this->stm->addColumn($name, 'text');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function mediumText($name)
    {
        $this->stm->addColumn($name, 'mediumtext');
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function smallText($name)
    {
        $this->stm->addColumn($name, 'tinytext');
        return $this;
    }

    /**
     * @return $this
     */
    public function null()
    {
        $this->stm->makeNull();
        return $this;
    }

    /**
     * @alias default
     *
     * @param $value
     * @return $this
     */
    public function defaultValue($value)
    {
        $this->stm->setDefault($value);

        return $this;

    }

    /**
     * @return $this
     */
    public function index()
    {
        $this->stm->makeIndex();
        return $this;

    }

    /**
     * @return $this
     */
    public function unique()
    {
        $this->stm->makeUnique();
        return $this;

    }

    /**
     * @param $columnName
     */
    public function after($columnName)
    {
        $this->stm->after($columnName);
    }

    /**
     * @param $otherTable
     * @param string $otherTableColumn
     * @param $name
     * @param string $type
     * @param int $length
     * @return $this
     */
    public function foreign($otherTable, $otherTableColumn = 'id', $name = null, $type = "int", $length = 11)
    {
        if (is_null($name)) {
            $name = Inflect::singularize($otherTable) . '_id';
        }

        if (!$this->stm->hasColumn($name) && !$this->alter) {
            $this->stm->addColumn($name, $type, $length);
        }

        $this->stm->setDefault(null);
        $this->stm->addConstraint('foreignKey', $name, $otherTable, $otherTableColumn);

        return $this;
    }

    /**
     * @param string $action
     * @return $this
     * @throws \Exception
     */
    public function onDelete($action = 'no action')
    {

        $this->stm->addConstrainAction('delete', $action);
        return $this;

    }

    /**
     * @param string $action
     * @return $this
     * @throws \Exception
     */
    public function onUpdate($action = 'no action')
    {
        $this->stm->addConstrainAction('update', $action);
        return $this;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->stm->getTableQuery($this->collate);
    }

    /**
     * Opens alter statement
     */
    public function alter()
    {
        $this->alter = true;
        $this->stm->alter();
    }

    /**
     * @param $columnName
     */
    public function drop($columnName)
    {
        $this->stm->dropColumn($columnName);
    }

    /**
     * @param $columnName
     */
    public function dropForeignKey($columnName)
    {
        $this->stm->dropForeignKey($columnName);
    }

    /**
     * @param $columnName
     * @param $type
     * @param $newName
     */
    public function rename($columnName, $type, $newName)
    {
        $this->renamedColumns[$newName] = $columnName;
        $this->stm->renameColumn($columnName, $type, $newName);

    }

    /**
     * @return $this
     */
    public function change()
    {
        $this->stm->setAlterModify();
        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (isset($this->MapMethod[$name])) {
            $method = $this->MapMethod[$name];
            call_user_func_array([$this, $method], $arguments);
        } else {
            throw new \Exception("Invalid method $name");
        }
    }

    /**
     * gets renamed columns
     *
     * @return array
     */
    public function getRenamedColumns()
    {
        return $this->renamedColumns;
    }
}