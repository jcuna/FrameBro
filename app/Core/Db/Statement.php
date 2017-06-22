<?php
/**
 * Author: Jon Garcia.
 * Date: 4/30/16
 * Time: 10:28 PM
 */

namespace App\Core\Db;

/**
 * Class Statement
 * @package App\Core\Model
 */
class Statement {

    /**
     * SQL clauses by keys
     *
     * @var array
     */
    private $clauses = [
        'groupBy'       => null,
        'group_concat'  => [],
        'concat'        => null,
        'limit'         => null,
        'offset'        => null,
        'order'         => null,
    ];

    /**
     * Certain types of select
     *
     * @var string
     */
    private $select;

    /**
     * The table name to use while building query string
     *
     * @var string
     */
    private $table;

    /**
     * Holds hash map of binding
     *
     * @var array
     */
    private $bindings = [];

    /**
     * Holds grammar to create where statements
     *
     * @var array
     */
    private $wheres = [];

    /**
     * Holds sql Grammar;
     *
     * @var array
     */
    private $query = [];

    /**
     * Holds all field types grammar
     *
     * @var array
     */
    private $typeString = [
        'primary'    => "INT(:length:) AUTO_INCREMENT PRIMARY KEY",
        'bigPrimary' => "BIGINT(:length:) UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        'bigint'     => "BIGINT(:length:) NOT NULL",
        'mediumint'  => "MEDIUMINT(:length:) NOT NULL",
        'tinyint'    => "TINYINT(:length:) NOT NULL",
        'int'        => "INT(:length:) NOT NULL",
        'varchar'    => "VARCHAR(:length:) NOT NULL",
        'date'       => "DATE NOT NULL",
        'datetime'   => "DATETIME NOT NULL",
        'time'       => "TIME NOT NULL",
        'timestamp'  => "TIMESTAMP NOT NULL",
        'binary'     => "BINARY(:length:) NOT NULL",
        'bool'       => "BOOLEAN NOT NULL",
        'decimal'    => "DECIMAL(:length:) NOT NULL",
        'double'     => "DOUBLE(:length:) NOT NULL",
        'enum'       => "ENUM(:length:) NOT NULL",
        'float'      => "FLOAT(:length:) NOT NULL",
        'text'       => "TEXT NOT NULL",
        'tinytext'   => "TINYTEXT NOT NULL",
        'mediumtext' => "MEDIUMTEXT NOT NULL",
        'longtext'   => "LONGTEXT NOT NULL",
        'serial'     => "SERIAL",
        'json'       => "JSON NOT NULL"
    ];

    /**
     * Holds all field types
     *
     * @var array
     */
    private $type = [
        'primary'    => "INT",
        'bigPrimary' => "BIGINT",
        'bigint'     => "BIGINT",
        'mediumint'  => "MEDIUMINT",
        'tinyint'    => "TINYINT",
        'int'        => "INT",
        'varchar'    => "VARCHAR",
        'date'       => "DATE",
        'datetime'   => "DATETIME",
        'time'       => "TIME",
        'timestamp'  => "TIMESTAMP",
        'binary'     => "BINARY",
        'bool'       => "BOOLEAN",
        'decimal'    => "DECIMAL",
        'double'     => "DOUBLE",
        'enum'       => "ENUM",
        'float'      => "FLOAT",
        'text'       => "TEXT",
        'tinytext'   => "TINYTEXT",
        'mediumtext' => "MEDIUMTEXT",
        'longtext'   => "LONGTEXT",
        'serial'     => "SERIAL",
        'json'       => "JSON"
    ];

    /**
     * @var array
     */
    private $constraints = [

        "foreignKey" => "CONSTRAINT `fk_framebro_:random:` FOREIGN KEY (`:name:`) REFERENCES `:table:`(`:id:`)"
    ];

    private $default = [

        "update"           => "ON UPDATE",
        "delete"           => "ON DELETE",
        "currentTimeStamp" => "CURRENT_TIMESTAMP"

    ];

    /**
     * Modify statements to be of alter types
     *
     * @var bool
     */
    private $alterStatements = false;

    /**
     * The keyword use to alter columns
     *
     * @var string
     */
    private $alterKeyword = "ADD";


    /**
     * Holds primary field of a table;
     *
     * @var string
     */
    private $primary;

    /**
     * Holds instances of statement objects by table key
     *
     * @var array
     */
    private static $statements = [];

    /**
     * represents column counts
     *
     * @var int
     */
    private $columnCount = 0;

    /*
     |-----------------------|
     | Sql grammar constants |
     |-----------------------|
     */

    /**
     * Select string
     */
    const SELECT = "SELECT";

    /**
     * Insert string
     */
    const INSERT = "INSERT INTO";

    /**
     * Values string
     */
    const VALUES = "VALUES";

    /**
     * Where string
     */
    const WHERE = "WHERE";

    /**
     * From string
     */
    const FROM = "FROM";

    /**
     * And string operator
     */
    const OPERATOR_AND = "AND";

    /**
     * OR string operator
     */
    const OPERATOR_OR = "OR";

    /**
     * Limit string
     */
    const LIMIT = "LIMIT";

    /**
     * Offset string
     */
    const OFFSET = "OFFSET";

    /**
     * Order by string
     */
    const ORDER = "ORDER BY";

    /**
     * Group by string
     */
    const GROUP_BY = "GROUP BY";

    /**
     * Group concat string
     */
    const GROUP_CONCAT = "GROUP_CONCAT";

    /**
     * Concat string
     */
    const CONCAT = "CONCAT";

    /**
     * Select distinct string
     */
    const SELECT_DISTINCT = "SELECT DISTINCT";

    /**
     * Inner join string
     */
    const INNER_JOIN = "INNER JOIN";

    /**
     * Left join string
     */
    const LEFT_JOIN = "LEFT JOIN";

    /**
     * Right join string
     */
    const RIGHT_JOIN = "RIGHT JOIN";

    /**
     * On string
     */
    const ON = "ON";

    /**
     * Delete statement.
     */
    const DELETE = "DELETE FROM";

    /**
     * Update statement
     */
    const UPDATE = "UPDATE";

    /**
     * Set for update statement.
     */
    const SET = "SET";

    /**
     * IN statement
     */
    const IN = "IN";

    /**
     * Between statement
     */
    const BETWEEN = "BETWEEN";

    /**
     * Create table statement
     */
    const CREATE = "CREATE TABLE IF NOT EXISTS";

    /**
     * Alter table
     */
    const ALTER = "ALTER TABLE";

    /**
     * Drop table statement
     */
    const DROP_TABLE = "DROP TABLE";

    /**
     * Drop column statement
     */
    const DROP_COLUMN = "DROP COLUMN";

    /**
     * Drop constraint statement
     */
    const DROP_CONSTRAINT = "DROP CONSTRAINT";

    /**
     * Statement constructor.
     * @param $table
     */
    private function __construct($table)
    {
        $this->table = $table;
    }

    /**
     * Get the appropriate instance of statement.
     *
     * @param $table
     * @return mixed
     */
    public static function getStatement($table)
    {
        if (isset(self::$statements[$table])) {
            return self::$statements[$table];
        }

        return self::$statements[$table] = new static($table);
    }

    /**
     * Destroy current statement run and creates new one.
     */
    private function reboot()
    {
        self::$statements[$this->table] = new static($this->table);
    }

    /**
     * Set bindings hash map
     *
     * @param $key
     * @param $binding
     */
    public function setBindings($key, $binding)
    {
        $this->bindings[$key] = $binding;
    }

    /**
     * Overrides any previously set bindings.
     *
     * @param array $bindings
     */
    public function setArrayBindings(array $bindings)
    {
        $this->bindings = $bindings;
    }

    /**
     * Get bindings
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Get the select method for this statement.
     *
     * @param bool $distinct
     * @return string
     */
    public function getSelect($distinct = false)
    {
        if ($distinct) {

            return self::SELECT_DISTINCT;

        } elseif (!is_null($this->select)) {

            return $this->select;

        } else {

            return self::SELECT;
        }
    }

    /**
     * Set a where statement.
     *
     * @param $columnName
     * @param $comparison
     * @param $value
     */
    public function setWhere($columnName, $comparison, $value)
    {
        if (!isset($this->wheres[0])) {

            $this->wheres[] = self::WHERE . " $columnName $comparison $value";
        } else {

            $this->setAnd($columnName, $comparison, $value);
        }
    }

    /**
     * Set between statement.
     *
     * @param $columnName
     * @param $valueA
     * @param $valueB
     */
    public function setBetween($columnName, $valueA, $valueB)
    {
        $statement = " $columnName " . self::BETWEEN . " $valueA " . self::OPERATOR_AND . " $valueB";

        if (!isset($this->wheres[0])) {

            $this->wheres[] = self::WHERE . $statement;

        } else {
            $this->wheres[] = self::OPERATOR_AND . $statement;
        }
    }

    /**
     * Set a where ... and statement.
     * @param $columnName
     * @param $comparison
     * @param $value
     */
    public function setAnd($columnName, $comparison, $value)
    {
        $this->wheres[] = self::OPERATOR_AND . " $columnName $comparison $value";
    }

    /**
     * Set a where ... or statement.
     *
     * @param $columnName
     * @param $comparison
     * @param $value
     */
    public function setOr($columnName, $comparison, $value)
    {
        $this->wheres[] = self::OPERATOR_OR . " $columnName $comparison $value";
    }

    /**
     * Set a where IN statement.
     *
     * @param $columnName
     * @param $bindings
     * @internal param $comparison
     * @internal param $value
     */
    public function setWhereIn($columnName, $bindings)
    {
        $this->wheres[] = self::WHERE . " $columnName " . self::IN . " ($bindings)";
    }

    /**
     * Set a join statement.
     *
     * @param $joinType
     * @param $rightTable
     * @param $leftColumn
     * @param $rightColumn
     */
    public function setJoin($joinType, $rightTable, $leftColumn, $rightColumn)
    {
        $types = [
            'inner' => self::INNER_JOIN,
            'left'  => self::LEFT_JOIN,
            'right' => self::RIGHT_JOIN
        ];

        $joinString = $types[$joinType] . " $rightTable " . self::ON . " $leftColumn = $rightColumn";

        $this->setJoinString($joinString);

    }

    /**
     * Set a join string.
     *
     * @param $string
     */
    public function setJoinString($string) {

        $this->clauses['joins'][] = $string;
    }

    /**
     * get where statement
     *
     * @return string
     */
    public function getWheres()
    {

        return " " . implode(" ", $this->wheres) . " ";
    }

    /**
     * Set limit statement.
     * @param $int
     */
    public function limit($int)
    {
        $this->clauses['limit'] = self::LIMIT . " $int";
    }

    /**
     * Set offset statement
     *
     * @param $int
     */
    public function offset($int)
    {
        $this->clauses['offset'] = self::OFFSET . " $int";
    }

    /**
     * Set Order statement.
     *
     * @param $column
     */
    public function order($column)
    {
        $this->clauses['order'] = self::ORDER . " $column";
    }

    /**
     * Set group by statement.
     *
     * @param $field
     */
    public function groupBy($field)
    {
        $this->clauses['groupBy'] = self::GROUP_BY . " $field";
    }

    /**
     * Set select distinct statement.
     *
     * @param $table
     */
    public function distinctWithTable($table)
    {

        $this->select = self::SELECT_DISTINCT . " $table.* ";
    }

    /**
     * Get a query statement or string value set in clauses.
     *
     * @param $key
     * @return bool | array
     */

    /**
     * @param $key
     * @return null|array
     */
    public function getQueryClause($key)
    {
        if (isset($this->clauses[$key])) {
            return $this->clauses[$key];
        }
        return null;
    }

    /**
     * Set a group concat statement.
     *
     * @param array $fields
     */
    public function groupConcat(array $fields)
    {
        $i = 0;
        foreach( $fields as $field => $display_as ) {
            $this->clauses['group_concat'][$i] = self::GROUP_CONCAT . "($field) $display_as";
            $this->clauses['group_concat_property'][] = $display_as;
            if ($display_as !== end($fields)) {
                $this->clauses['group_concat'][$i] .= ",";
            }
            $i++;
        }
    }

    /**
     * Set a concat statement
     *
     * @param $concat
     */
    public function concat($concat)
    {
        $this->clauses['concat'] = self::CONCAT . " $concat";
    }

    /**
     * Get select query statement for this instance.
     *
     * @param array $projection
     * @param bool $distinct
     * @return string
     */
    public function getQuery(array $projection, $distinct = false)
    {
        $projection = implode(', ', $projection);

        $statement = $this->getSelect($distinct) . " $projection" . $this->getFrom() . $this->getConditions();

        //Reset statement
        $this->reboot();

        return trim($statement);

    }

    public function getDelete()
    {
        $statement = self::DELETE . " $this->table " . $this->getWheres();

        //Reset statement
        $this->reboot();

        return trim($statement);
    }

    public function getUpdate($columnsBindHash)
    {
        $partialStatement = $this->getUpdateString($columnsBindHash);

        $partialStatement .= $this->getWheres();

        $statement = self::UPDATE . " $this->table " . self::SET . " $partialStatement";

        $this->reboot();

        return trim($statement);

    }

    /**
     * Insert statement.
     *
     * @param $columnsBindHash
     * @return string
     * @internal param $columns
     */
    public function getInsert($columnsBindHash)
    {
        $columns = $this->concatenateStatement($columnsBindHash);
        $bindings = $this->concatenateStatementKeys($columnsBindHash);

        $this->reboot();

        return trim(self::INSERT . " $this->table ($columns) " . self::VALUES . " ($bindings)");

    }

    /**
     * Gets a multi insert statement
     *
     * @param $columns
     * @param $bindingHash
     * @return string
     */
    public function getMultiInsert($columns, $bindingHash)
    {
        $strColumns = $this->concatenateStatement($columns);
        $bindings = $this->concatenateMultiValue($bindingHash);

        $this->reboot();

        return trim(self::INSERT . " $this->table ($strColumns) " . self::VALUES . " $bindings");
    }

    /**
     * Get the join array
     *
     * @return mixed
     */
    public function getJoins()
    {
        return $this->clauses['joins'];
    }

    /**
     * @param array $hash
     * @return string
     * @internal param $division
     */
    private function concatenateMultiValue(array $hash)
    {
        $strPartialStatement = '';

        foreach ($hash as $item) {

            $strPartialStatement .= '(';

            $strPartialStatement .= $this->concatenateStatement($item);

            if (end($hash) !== $item) {
                $strPartialStatement .= '), ';
            } else {
                $strPartialStatement .= ')';
            }
        }

        return $strPartialStatement;
    }

    /**
     * @param array $hash
     * @return string
     */
    public function concatenateStatement(array $hash)
    {
        return implode(', ', $hash);
    }

    /**
     * @param array $hash
     * @return string
     */
    public function concatenateStatementKeys(array $hash)
    {
        return implode(', ', array_keys($hash));
    }

    /**
     * Converts columns bindings hash map into statement.
     * @param $columns
     * @return string
     */
    private function getUpdateString($columns)
    {
        $statement = '';
        foreach ($columns as $bind => $column) {
            $statement .= $column . ' = ' . $bind;
            if (end($columns) !== $column) {
                $statement .=  ', ';
            }
        }

        return $statement;
    }

    /**
     * Get from partial statement.
     *
     * @return string
     */
    private function getFrom()
    {
        return $this->getGroupConcatString() . self::FROM . " $this->table ";
    }

    /**
     * get concat partial statement.
     *
     * @return string
     */
    private function getConcatString()
    {
        $concatString = '';
        if (!empty($this->clauses['concat'])) {
            $concatString =  $this->clauses['concat'];
        }

        return $concatString;
    }

    /**
     * Get group concat partial statement.
     *
     * @return string
     */
    private function getGroupConcatString()
    {
        $groupConcat = ' ';

        $space = '';
        if (!empty($this->clauses['group_concat'])) {
            $group_concat = ', ';
            foreach($this->clauses['group_concat'] as $concat) {
                $group_concat .= $space . $concat;
                $space = ' ';
            }
            $groupConcat = "$group_concat ";
        }

        return $groupConcat;
    }

    /**
     * Get join statements
     *
     * @return string
     */
    private function getJoinsString()
    {
        $joinString = '';

        if (!empty($this->clauses['joins'])) {

            $joinString .= implode(' ', $this->clauses['joins']);
        }

        return $joinString;
    }

    /**
     * Get conditions partial statement.
     *
     * @return string
     */
    public function getConditions()
    {
        $strConditions = $this->getConcatString();
        $strConditions .= $this->getJoinsString();
        $strConditions .= $this->getWheres();

        if (!empty($this->clauses['groupBy'])) {
            $strConditions .= " {$this->clauses['groupBy']}";
        }

        if (!empty($this->clauses['limit'])) {
            $strConditions .= " {$this->clauses['limit']}";
        }

        if (!empty($this->clauses['offset'])) {
            $strConditions .= " {$this->clauses['offset']}";
        }

        return $strConditions;
    }

    /////-------Grammar to create tables--------/////

    /**
     * @param null $collate
     * @return string
     */
    public function getTableQuery($collate = null)
    {
        $string = $this->getInitialGrammar();

        $conclusionStatementsArray = $this->getQueryConclusions();

        if (!empty($conclusionStatementsArray)) {
            if ($this->columnCount) {
                $string .= "," . PHP_EOL;
            }

            $string .= implode(',' . PHP_EOL, $conclusionStatementsArray);
        }

        if (!$this->alterStatements) {
            $string .= PHP_EOL . ")";
        }

        if (!is_null($collate) && !$this->alterStatements) {
            $string .= PHP_EOL . "COLLATE $collate";
        }

        $this->reboot();

        return trim($string);
    }

    /**
     * Helper method
     *
     * @return string
     */
    private function getInitialGrammar()
    {
        $grammar = $this->getQueryGrammar();
        $string = $grammar[0] . PHP_EOL;
        unset($grammar[0]);

        $string .= implode(',' . PHP_EOL, $grammar);

        return $string;
    }


    /**
     * @return array
     */
    public function getQueryGrammar()
    {
        $out = [];
        foreach ($this->query as $item) {

            if (!isset($item['grammar'])) {
                continue;
            }

            $out[] = $item['grammar'];
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getQueryConclusions()
    {
        $out = [];
        foreach ($this->query as $item) {
            if (isset($item['conclusions'])) {
                $out[] = $item['conclusions'];
            }
        }

        return $out;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getDefault($key) {

        if (!is_null($key)) {
            if (isset($this->default[$key])) {
                return $this->default[$key];
            }
        } elseif (is_null($key)) {
            return "NULL";
        }

        return $key;
    }

    /**
     * @param $value
     */
    public function setDefault($value)
    {
        $key = $this->getCurrentQueryKey();

        $subject = '';
        if (isset($this->query[$key]['grammar'])) {
            $subject = $this->query[$key]['grammar'];
        }

        $value = "DEFAULT " . $this->getDefault($value);

        $grammar = trim(str_replace("NOT NULL", $value, $subject ));

        $this->query[$key]['grammar'] = $grammar;

    }

    /**
     * Get array key to last query grammar
     *
     * @return integer
     */
    public function getCurrentQueryKey()
    {
        end($this->query);
        return key($this->query);
    }

    /**
     * Make last column unsigned
     */
    public function makeUnsigned()
    {
        $key = $this->getCurrentQueryKey();

        $subject = $this->query[$key]['grammar'];
        $type = $this->query[$key]['type'];

        $this->query[$key]['grammar'] = str_replace($type, "$type UNSIGNED", $subject );
    }

    /**
     * Make a column accept null values
     */
    public function makeNull()
    {
        $key = $this->getCurrentQueryKey();

        $subject = $this->query[$key]['grammar'];

        $grammar = trim(str_replace("NOT NULL", "", $subject ));

        $this->query[$key]['grammar'] = $grammar;
    }

    /**
     * Make a column unique
     */
    public function makeUnique()
    {
        $key = $this->getCurrentQueryKey();

        $subject = $this->query[$key]['grammar'] . " UNIQUE";

        $this->query[$key]['grammar'] = $subject;
    }

    /**
     * Make a column index
     * @param null $key
     * @param null $name
     */
    public function makeIndex($key = null, $name = null)
    {
        $key = is_null($key) ? $this->getCurrentQueryKey() : $key;

        $name = is_null($name) ? $this->query[$key]['name'] : $name;

        $grammar = !$this->alterStatements ? "INDEX (`$name`)" : "$this->alterKeyword INDEX (`$name`)";

        $this->query[$key]['conclusions'] = $grammar;
    }

    /**
     * Moves a column after another
     *
     * @param $columnName
     */
    public function after($columnName)
    {
        $key = $this->getCurrentQueryKey();

        $subject = $this->query[$key]['grammar'] . " AFTER `$columnName`";

        $this->query[$key]['grammar'] = $subject;
    }

    /**
     * @param $type
     * @param $name
     * @param null $otherTable
     * @param null $otherTableColumn
     * @throws \Exception
     */
    public function addConstraint($type, $name, $otherTable = null, $otherTableColumn = null)
    {
        if (!isset($this->constraints[$type])) {
            throw new \Exception("Invalid constraint type $type");
        }

        $constraintName = $this->constraints[$type];
        $search = [":random:", ":name:", ":table:", ":id:",];
        $replace = [$name, $name, $otherTable, $otherTableColumn];

        $grammar = str_replace($search, $replace, $constraintName);

        $key = $this->getCurrentQueryKey();
        $key++;

        if (!$this->alterStatements) {
            $this->makeIndex($key, $name);
            $key++;
        }

        $this->query[$key]['name'] = $name;
        $this->query[$key]['type'] = 'constraint';
        $this->query[$key]['conclusions'] = !$this->alterStatements ? $grammar : "$this->alterKeyword $grammar";
    }

    /**
     * @param $actionName
     * @param $action
     * @throws \Exception
     */
    public function addConstrainAction($actionName, $action)
    {
        $actions = $this->default;

        if (!isset($actions[$actionName])) {
            throw new \Exception('Invalid action name');
        }

        $key = $this->getCurrentQueryKey();

        $action = strtoupper($action);

        $currentGrammar = $this->query[$key]['conclusions'];
        $this->query[$key]['conclusions'] = $currentGrammar . PHP_EOL . "$actions[$actionName] $action";

    }

    /**
     * @param $type
     * @param $name
     * @param null $length
     * @param null $pos
     * @throws \Exception
     */
    public function setGrammar($type, $name, $length = null, $pos = null)
    {
        if (!isset($this->typeString[$type])) {
            throw new \Exception("invalid type $type");
        }

        $stm = $this->typeString[$type];

        if ($length === null) {
            $grammar = str_replace('(:length:)', '', $stm);
        } else {
            $grammar = str_replace(':length:', $length, $stm);
        }

        $key = $this->getCurrentQueryKey();
        $key++;

        $this->query[$key]['name'] = $name;
        $this->query[$key]['type'] = $this->type[$type]."($length)";

        $statement = !$this->alterStatements ? "`$name` $grammar" : "$this->alterKeyword `$name` $grammar";

        $this->query[$key]['grammar'] = $statement;

        $this->getColumnDesiredPosition($key, $pos, $name);
    }

    /**
     * Helper method
     *
     * @param $key
     * @param $pos
     * @param $name
     */
    private function getColumnDesiredPosition($key, $pos, $name)
    {
        if (is_null($pos)) {
            return;
        }

        if ($pos !== $key) {

            foreach ($this->query as $k => $l) {
                if (array_search($name, $l)) {
                    $oldPos = $k;
                }
            }

            if (isset($oldPos)) {
                shiftElement($this->query, $oldPos, $pos);
            }
        }
    }

    /**
     * @param $value
     */
    public function onUpdate($value)
    {
        $key = $this->getCurrentQueryKey();

        $subject = $this->query[$key]['grammar'];

        $replace = $this->getDefault('update') . " " . $this->getDefault($value);

        $grammar = str_replace("NOT NULL", $replace, $subject);

        $this->query[$key]['grammar'] = $grammar;

    }

    /**
     * Set timestamps
     */
    public function addTimeStamps()
    {
        $this->setGrammar('timestamp', 'created_at');
        $this->setDefault('currentTimeStamp');

        $this->setGrammar('timestamp', 'updated_at');
        $this->onUpdate('currentTimeStamp');
    }

    /**
     * Create table grammar
     *
     * @internal param $table
     */
    public function create()
    {
        $this->query[0]['type'] = self::CREATE;
        $this->query[0]['grammar'] = self::CREATE . " `$this->table` (";
    }

    /**
     * Create table grammar
     *
     * @internal param $table
     */
    public function alter()
    {
        $this->query[0]['type'] = self::ALTER;
        $this->query[0]['grammar'] = self::ALTER . " `$this->table`";
    }

    /**
     * Drop a table
     */
    public function drop()
    {
        $this->setAlterStatements();
        $this->alterStatements = true;
        $this->query[0]['grammar'] = self::DROP_TABLE . " $this->table";
    }

    /**
     * Drop a table
     *
     * @param $columnName
     */
    public function dropColumn($columnName)
    {
        $this->query[]['grammar'] = self::DROP_COLUMN . " $columnName";
    }

    /**
     * Drop constraint
     *
     * @param $columnName
     */
    public function dropForeignKey($columnName)
    {
        $this->query[]['grammar'] = self::DROP_CONSTRAINT . " `fk_framebro_$columnName";
    }

    /**
     * Add table column grammar
     *
     * @param $name
     * @param $length
     * @param $type
     */
    public function addColumn($name, $type, $length = null)
    {
        $this->columnCount++;

        $pos = null;

        if ($type === 'primary') {
            $pos = 1;
            $this->primary = $name;
        }

        if (is_array($length)) {
            $length = $this->concatenateWithQuotes($length);
        }

        $this->setGrammar($type, $name, $length, $pos);

        $this->resetAlterKeyword();
    }

    /**
     * @param $columnName
     * @param $type
     * @param $newName
     */
    public function renameColumn($columnName, $type, $newName)
    {
        $type = strtoupper($type);

        $this->query[]['grammar'] = "CHANGE `$columnName` `$newName` $type";
    }

    /**
     * @param array $array
     * @return string
     */
    public function concatenateWithQuotes(array $array)
    {
        $out = '';
        foreach ($array as $li) {
            if (end($array) !== $li) {
                $out .= "'$li',";
            } else {
                $out .= "'$li'";
            }
        }

        return $out;
    }

    /**
     * If a column exists
     *
     * @param $column
     * @return bool
     */
    public function hasColumn($column)
    {
        foreach ($this->query as $item) {
            if (array_search($column, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasPrimary()
    {
        if (!is_null($this->primary))
        {
            return true;
        }

        return false;
    }

    /**
     * Change alter keyword to modify
     */
    public function setAlterModify()
    {
        $this->alterKeyword = "MODIFY";
    }

    /**
     * Reset alter keyword to add
     */
    public function resetAlterKeyword()
    {
        $this->alterKeyword = "ADD";
    }

    /**
     * Set to return alter statements.
     */
    public function setAlterStatements()
    {
        $this->alterStatements = true;
    }
}