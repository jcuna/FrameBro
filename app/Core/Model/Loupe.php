<?php
/**
 * Created By: Jon Garcia
 * Date: 1/13/16
 */

namespace App\Core\Model;

use App\Core\Interfaces\Arrayable;
use App\Core\Interfaces\DatabaseAccessInterface;
use App\Core\Interfaces\Jsonable;
use App\Core\Interfaces\ModelInterface;
use App\Core\Cache\Cache;
use App\Core\Db\Database;
use App\Core\Db\DatabaseManager;
use App\Core\Db\Statement;
use App\Core\Db\StatementManager;
use App\Core\Exceptions\ModelException;
use App\Core\Html\WebForm;
use App\Core\Libraries\Inflect;

/**
 * Class Loupe
 * @package App\Core\Model
 */
abstract class Loupe implements ModelInterface, DatabaseAccessInterface, \IteratorAggregate, Arrayable, Jsonable
{
    /**
     * The attributes object containing all properties of the model.
     *
     * @var Attributes
     */
    public $attributes;

    /**
     * rows count from the db.
     *
     * @var integer
     */
    public $count;

    /**
     * The last record inserted in the db by primary key.
     *
     * @var number
     */
    public $lastId;

    /**
     * Weather the query has executed.
     * @var bool
     */
    public $executed = false;

    /**
     * Holds the current query.
     * @var
     */
    public $query;

    /**
     * lower case model name without namespace.
     *
     * @var string
     */
    protected $model;

    /**
     * The primary id key.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The table name.
     *
     * @var string
     */
    protected $table;

    /**
     * Holds the configuration name for the current db connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The column name representing the time of record creation.
     *
     * @const string
     */
    const TIME_CREATED = 'created_at';

    /**
     * The column name representing the time of last time record was updated.
     *
     * @var string
     */
    const TIME_UPDATED = 'updated_at';

    /**
     * Property holding the time the record was inserted.
     *
     * @var \DateTime
     */
    protected $created_at;

    /**
     * Property holding the time the record was updated.
     *
     * @var \DateTime
     */
    protected $updated_at;

    /**
     * If set to true, created_at and updated_at will not be used.
     *
     * @var bool
     */
    protected $customTime = false;

    /**
     * The default format for the updated_at and created_at columns.
     * Other option is 'dateTime'
     *
     * @var string
     */
    protected $timeFormat = 'dateTime';

    /**
     * Weather the current query wants to find all results
     *
     * @var bool
     */
    private $findAll = false;

    /**
     * Weather we want distinct values.
     *
     * @var bool
     */
    private $distinctValues = false;

    /**
     * Holds the value of the foreign key.
     *
     * @var string
     */
    private $foreignKey;

    /**
     * Holds the most recent related model in the transaction.
     *
     * @var
     */
    private $relatedModel;

    /**
     * Hold previous query conditions
     *
     * @var array
     */
    private $conditions = [];

    /**
     * Holds all related models
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Holds the local key.
     *
     * @var
     */
    private $localKey;

    /**
     * If there's a pivot table, its value is saved here.
     *
     * @var
     */
    private $pivotTable;

    /**
     * Holds any errors present in query.
     *
     * @var
     */
    protected $SQLError = array();

    /**
     *
     * Weather a join query is setup.
     *
     * @var bool
     */
    private $hasJoin = false;

    /**
     * Letter use for PDO binding.
     *
     * @var string
     */
    private static $letterBind = 'a';

    /**
     * The prefix for SQL query bindings.
     */
    const BINDING_PREFIX = ':bind_';

    /**
     * Loupe constructor.
     * @param null $arAttributes
     */
    public function __construct($arAttributes = null)
    {
        $this->setTable();

        $this->model = self::getSelfModelName();
        /** @var Attributes attributes */
        $this->attributes = $this->collectMassAssignments($arAttributes);
    }

    /**
     * @param $attributes
     * @return Attributes
     */
    public function collectMassAssignments($attributes)
    {
        if (!is_null($attributes) && is_array($attributes) && $this->isAssociative($attributes)) {
            return new Attributes($attributes);
        }

        return new Attributes();
    }

    /**
     * Get the current instance of Statement.
     *
     * @return Statement
     */
    public function getStatement()
    {
        return Statement::getStatement($this->table);
    }

    /**
     * Set table name is it has not be set.
     */
    private function setTable()
    {
        if (is_null($this->table)) {
            $this->table = $this->getTableName();
        }
    }

    /**
     * @return string
     */
    public function getSelfModelName()
    {
        $namespace = strtolower(get_class($this));
        $pos = strrpos($namespace, '\\');
        $name = $pos === false ? $namespace : substr($namespace, $pos + 1);
        return $name;
    }

    /**
     * Returns the current letter binding.
     *
     * @return string
     */
    private function getLetterBinding()
    {
        $letterBind = self::$letterBind;

        self::$letterBind++;

        return $letterBind;
    }

    /**
     * Concatenates a sql binding key.
     *
     * @return string
     */
    private function getNamedParam()
    {
        return self::BINDING_PREFIX . $this->getLetterBinding();
    }

    /**
     * Get question marks param bindings
     *
     * @param $bindings
     * @return string
     */
    private function getUnNamedParam($bindings)
    {
        return str_repeat('?,', count($bindings) - 1) . '?';
    }

    /**
     * @param null $id
     * @param array $selection
     * @return $this|ModelCollection|Loupe
     */
    public function find($id, array $selection = ['*'])
    {
        $field = $this->hasJoin ? $this->table . '.' . $this->primaryKey : $this->primaryKey;

        if (is_array($id)) {
            $this->whereIn($field, $id);
        } else {
            $this->where($field, $id);
        }

        $collection = $this->get($selection);

        if (!$collection->isEmpty()) {
            if ($collection->count() === 1) {
                return $collection->first();
            } else {
                return $collection;
            }
        }

        return $this;
    }

    /**
     * find all records
     *
     * @param array $selection
     * @return ModelCollection
     * @throws ModelException
     */
    public function all(array $selection = ["*"])
    {
        if (!empty(trim($this->getStatement()->getConditions()))) {
            $trace = debug_backtrace()[0];
            throw new ModelException(
                "No conditions possible when getting all records",
                8000,
                $trace['file'],
                $trace['line']
            );
        }
        $this->findAll = true;
        return $this->get($selection);
    }

    /**
     * Sets a select distinct query
     * @return $this
     */
    public function distinct() {
        $this->distinctValues = true;
        return $this;
    }

    /**
     * @param $field
     * @param $binding
     * @param string $comparison
     * @return $this
     */
    public function where($field, $binding, $comparison = "=")
    {
        $statement = $this->getStatement();
        $value = $this->getNamedParam();

        $statement->setWhere($field, $comparison, $value);
        $statement->setBindings($value, $binding);

        return $this;
    }

    /**
     * @param $field
     * @param $binding
     * @param string $comparison
     * @return $this
     */
    public function orWhere($field, $binding, $comparison = "=")
    {
        $statement = $this->getStatement();
        $value = $this->getNamedParam();

        $statement->setOr($field, $comparison, $value);
        $statement->setBindings($value, $binding);

        return $this;
    }

    /**
     * @param $field
     * @param $fromDate
     * @param $toDate
     * @return $this
     */
    public function between($field, $fromDate, $toDate)
    {
        $statement = $this->getStatement();
        $value = $this->getNamedParam();
        $valueB = $this->getNamedParam();

        $statement->setBetween($field, $value, $valueB);
        $statement->setBindings($value, $fromDate);
        $statement->setBindings($valueB, $toDate);

        return $this;
    }

    /**
     * Perform a WHERE IN statement
     *
     * @param $field
     * @param $bindings
     * @return $this
     */
    public function whereIn($field, array $bindings)
    {
        $statement = $this->getStatement();

        $statement->setWhereIn($field, $this->getUnNamedParam($bindings));
        $statement->setArrayBindings($bindings);

        return $this;
    }

    /**
     * Performs join
     *
     * @param $leftField
     * @param $rightField
     * @param $leftTable
     * @param string $joinType
     * @return $this
     * @throws ModelException
     */
    public function join($leftField, $rightField, $leftTable = null, $joinType = 'inner') {

        $this->hasJoin = true;
        if (!$this->fieldIsDotted($rightField) && is_null($leftTable)) {
            $trace = debug_backtrace()[0];
            throw new ModelException(
                'You must specify a table for at least the right field when
                $leftTable is null. i.e. "rightTable.rightField"',
                8000,
                $trace['file'],
                $trace['line']
            );
        }

        $leftColumn = $this->fieldIsDotted($leftField) ? $leftField : $this->table . '.' . $leftField;
        $rightColumn = is_null($leftTable) ? $rightField :
            $this->fieldIsDotted($rightField) ? $rightField : $leftTable . '.' . $rightField;
        $leftTable = !is_null($leftTable) ? $leftTable : explode('.', $leftField)[0];

        $this->getStatement()->setJoin($joinType, $leftTable, $leftColumn, $rightColumn);

        return $this;
    }

    /**
     * @param $field
     * @return bool
     */
    private function fieldIsDotted($field) {
        if (strpos($field, '.')) {
            return true;
        }

        return false;
    }

    /**
     * performs left join
     * @param $rightTable
     * @param $leftField
     * @param $rightField
     * @return $this|Loupe
     */
    public function leftJoin($leftField, $rightField, $rightTable = null) {
        return $this->join($leftField, $rightField, $rightTable, 'left');
    }

    /**
     * performs right join
     * @param $rightTable
     * @param $leftField
     * @param $rightField
     * @return $this|Loupe
     */
    public function rightJoin($leftField, $rightField, $rightTable = null) {
        return $this->join($leftField, $rightField, $rightTable, 'right');
    }

    /**
     * @param null $column
     * @return int
     */
    public function count($column = null)
    {
        if (is_null($column)) {
            $column = $this->primaryKey;
        }
        $this->get(["COUNT($column)"]);

        $arAttributes = iterator_to_array($this->attributes);
        $count = reset($arAttributes);

        $this->attributes = new Attributes();
        $this->count = intval($count);
        return $this->count;
    }

    /**
     * Save or update a model
     *
     * @param array|null $attributes
     * @return bool
     * @throws ModelException
     */
    public function save(array $attributes = null)
    {
        if (is_null($attributes)) {
            $attributes = $this->attributes;
        } else {
            $attributes = new Attributes($attributes);
        }

        if (empty((array) $attributes)) {

            $trace = debug_backtrace()[0];
            throw new ModelException(
                'No data to save',
                8000,
                $trace['file'],
                $trace['line']
            );
        }

        $this->setupTimeStamp($attributes);

        $primaryKey = null;
        if (isset($attributes->{$this->primaryKey})) {
            $primaryKey = $attributes->{$this->primaryKey};
            unset($attributes->{$this->primaryKey});

            if (!$this->customTime) {
                unset($attributes->{static::TIME_CREATED});
            }
        }

        $statement = $this->getStatement();
        $propertyNames = array();
        foreach($attributes as $property => $value) {

            $bind = $this->getNamedParam();
            $propertyNames[$bind] = $property;

            $statement->setBindings($bind, $value);
        }

        $action = $this->getAction($statement, $propertyNames, $primaryKey);

        return $this->execute($action, $statement);
    }

    /**
     * Helper method for the save method.
     *
     * @param Statement $statement
     * @param $propertyNames
     * @param $primaryKey
     * @return StatementManager
     */
    private function getAction(Statement $statement, $propertyNames, $primaryKey)
    {
        $conn = $this->getConnection();

        if (!is_null($primaryKey)) {

            $primaryKeyBind = $this->getNamedParam();

            $statement->setBindings($primaryKeyBind, $primaryKey);
            $statement->setWhere($this->primaryKey, '=', $primaryKeyBind);

            $this->query = $statement->getUpdate($propertyNames);
            $action = $conn->prepare($this->query);
            //adding primary id back to $this.
            $this->attributes->{$this->primaryKey} = $primaryKey;
        } else {
            $this->query = $statement->getInsert($propertyNames);
            $action = $conn->prepare($this->query);
        }

        return $action;
    }

    /**
     * Setup the proper value for the updated_at and created_at columns.
     *
     * @param Attributes $attributes
     */
    private function setupTimeStamp(Attributes $attributes)
    {
        if ($this->customTime === false) {

            $date = new \DateTime();

            if ($this->timeFormat === 'timeStamps') {
                $stamp = $date->getTimestamp();

            } else {
                $stamp = $date->format("Y-m-d H:i:s");
            }

            $this->created_at = $stamp;
            $this->updated_at = $stamp;

            $attributes->{static::TIME_CREATED} = $this->created_at;
            $attributes->{static::TIME_UPDATED} = $this->updated_at;
        }
    }

    /**
     * Insert multiple records
     *
     * @param array $records
     * @return bool
     */
    public function insert(array $records)
    {
        $Associative = $this->isAssociative($records);

        if ($Associative) {
            $records = [ $records ];
        }

        $statement = $this->getStatement();

        $bindingValueHash = $columns = [];
        $i = 0;
        foreach ($records as $record) {

            foreach ($record as $column => $value) {

                //get column names;
                $columns[$column] = $column;

                $bind = $this->getNamedParam();
                $bindingValueHash[$i][] = $bind;
                $statement->setBindings($bind, $value);
            }
            $i++;
        }

        $this->query = $statement->getMultiInsert($columns, $bindingValueHash);
        $conn = $this->getConnection();
        $query = $conn->prepare($this->query);

        return $this->execute($query, $statement);
    }

    /**
     * Deletes a persisted model
     *
     * @throws ModelException
     */
    public function delete()
    {
        $conn = $this->getConnection();

        if (!isset($this->attributes->{$this->primaryKey})) {
            $trace = debug_backtrace()[0];
            throw new ModelException(
                'No object has been loaded',
                8000,
                $trace['file'],
                $trace['line']
            );
        }
        $key = $this->attributes->{$this->primaryKey};

        $bind = $this->getNamedParam();

        $statement = $this->getStatement();
        $statement->setWhere($this->primaryKey, '=', $bind);
        $statement->setBindings($bind, $key);

        $this->query = $statement->getDelete();
        $delete = $conn->prepare($this->query);

        return $this->execute($delete, $statement);
    }

    /**
     * Execute query
     *
     * @param StatementManager $action
     * @param Statement $statement
     * @return bool
     * @throws ModelException
     */
    private function execute(StatementManager $action, Statement $statement)
    {
        $conn = $this->getConnection();

        if ($action->execute($statement->getBindings())) {

            $this->count = intval($action->rowCount());

            $this->lastId = $conn->lastInsertId();
            $this->{$this->primaryKey} = $this->lastId;

            $this->executed = true;

            return true;

        } else {

            $this->SQLError = $action->errorInfo();
            if (isset($this->SQLError[0]) && $this->SQLError[0] !== "00000") {
                $trace = debug_backtrace()[0];
                throw new ModelException(
                    $this->SQLError[2],
                    $this->SQLError[1],
                    $trace['file'],
                    $trace['line']
                );
            }
            return false;
        }
    }


    /**
     * regardless of the chained position of this method,
     * it will always be appended to the last part of
     * the statement unless there's an offset
     *
     * @param $int Integer
     * @return $this Object
     */
    public function limit($int)
    {
        $statement = $this->getStatement();

        $statement->limit($int);

        return $this;
    }

    /**
     * regardless of the chained position of this method,
     * it will always be appended to the last
     * part of the statement
     *
     * @param $int Integer
     * @return $this Object
     */
    public function offset($int)
    {
        $statement = $this->getStatement();
        $statement->offset($int);

        return $this;
    }

    /**
     * @param $column Integer
     * @return $this Object
     */
    public function order($column)
    {
        $statement = $this->getStatement();

        $statement->order($column);

        return $this;
    }

    /**
     * @param $field
     * @return $this
     */
    public function groupBy($field)
    {
        $statement = $this->getStatement();

        $statement->groupBy($field);

        return $this;
    }


    /**
     * @param array $fields
     * @return $this
     */
    public function groupConcat(array $fields)
    {
        $statement = $this->getStatement();

        $statement->groupConcat($fields);

        return $this;
    }

    /**
     * Very specific way to use this. See example below pay attention to quotes
     *
     * $test = new User();
     * $test->concat("username, '-', fname, '-', created_at");
     * @param $concatenatedFields
     * @param null $displayAs
     * @return $this
     */
    public function concat($concatenatedFields, $displayAs = null)
    {
        $concat = !is_null($displayAs) ? "($concatenatedFields) $displayAs" : "($concatenatedFields)";

        $statement = $this->getStatement();

        $statement->concat($concat);

        return $this;
    }

    /**
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        if (!isset($this->{$property})) {
            if (isset($this->attributes->{$property})) {
                return $this->attributes->{$property};
            }
        }
        return null;
    }

    /**
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        if (!isset($this->{$property})) {
            $this->attributes->{$property} = $value;
        }
    }

    /**
     * @param $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->attributes->{$name})) {
            unset($this->attributes->{$name});
        }
    }

    /**
     * @param array $fields
     * @return ModelCollection
     * @throws ModelException
     */
    public function get(array $fields = ['*'])
    {
        $statement = $this->getStatement();
        $this->recordConditions($statement);
        $this->query = $statement->getQuery($fields);

        //reset distinct values
        $this->distinctValues = false;

        $conn = $this->getConnection();

        $query = $conn->prepare($this->query);

        $this->executeGet($statement, $query);

        $this->executed = true;

        $this->count = intval($query->rowCount());

        $collection = $this->setAttributes($query, $statement);

        //Binds this model to the WebForm class for auto field binding
        WebForm::modelBinding($this);

        return $collection;
    }

    /**
     * @param Statement $statement
     */
    private function recordConditions(Statement $statement)
    {
        $this->conditions = [
            "conditions" => $statement->getConditions(),
            'bindings'  => $statement->getBindings()
        ];
    }

    /**
     * Executes a get query.
     * @param Statement $statement
     * @param StatementManager $query
     *
     * @throws ModelException
     */
    private function executeGet(Statement $statement, StatementManager $query)
    {
        $query->execute($statement->getBindings());

        $this->SQLError = $query->errorInfo();
        if (isset($this->SQLError[0]) && $this->SQLError[0] !== "00000") {
            $trace = debug_backtrace()[0];
            throw new ModelException(
                $this->SQLError[2],
                $this->SQLError[1],
                $trace['file'],
                $trace['line']
            );
        }
    }

    /**
     * @param Loupe $model
     * @param array $fetchedArray
     * @param null $concatProperty
     */
    private function BuildModelFromFetchedData(Loupe $model, array $fetchedArray, $concatProperty = null)
    {
        foreach ($fetchedArray as $property => $value) {
            if ($property === $this->primaryKey && is_null($value)) {
                $this->count = 0;
                continue;
            }
            if (!is_null($concatProperty)) {
                foreach($concatProperty as $propName) {
                    if ($propName === $property) {
                        $model->attributes->{$property} = explode(',', $value);
                    }
                }
            }
            if (!isset($model->attributes->{$property})) {
                $model->attributes->{$property} = $value;
            }
        }
    }

    /**
     * Set attributes
     *
     * @param StatementManager $statement
     * @param Statement $statementBuilder
     * @return ModelCollection
     * @throws ModelException
     */
    private function setAttributes(StatementManager $statement, Statement $statementBuilder)
    {
        $collection = new ModelCollection();
        $concatProperty = $statementBuilder->getQueryClause('group_concat_property');
        if ($this->count === 0) {
            $this->SQLError = $statement->errorInfo();
            if (isset($this->SQLError[0]) && $this->SQLError[0] !== "00000") {
                $trace = debug_backtrace()[0];
                throw new ModelException(
                    $this->SQLError[2],
                    $this->SQLError[1],
                    $trace['file'],
                    $trace['line']
                );
            }
            return $collection;
        }
        if ($this->count === 1) {

            $this->BuildModelFromFetchedData(
                $this,
                $statement->fetch(\PDO::FETCH_ASSOC),
                $concatProperty
            );
            $collection->push($this);
        } else {
            while ($fetchedArray = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $model = new static;
                $model->count = $this->count;
                $this->BuildModelFromFetchedData($model, $fetchedArray, $concatProperty);
                $collection->push($model);
            }
        }
        return $collection;
    }

    /**
     * @return array|bool
     */
    public function toArray() {
        return $this->attributes->toArray();
    }

    /**
     * Return attributes as json string.
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * @param array $selection
     * @return $this|Loupe
     */
    public function first($selection = ["*"])
    {
        $collection = $this->limit(1)->get($selection);
        if (!$collection->isEmpty()) {
            return $collection->first();
        }

        return $this;
    }

    /**
     * @param $relationship
     * @return $this
     */
    public function with($relationship)
    {
        if (method_exists($this, $relationship)) {
            $this->$relationship();

        } else {
            $this->addJoinsFromRelatedModel($relationship);
        }
        return $this;
    }

    /**
     * If the relationship does not exist in this object,
     * look in child object to see if relationship exists.
     *
     * @param $relationship
     */
    private function addJoinsFromRelatedModel($relationship)
    {
        /** @var Loupe $model */
        foreach ($this->relationships as $model) {
            if (method_exists($model, $relationship)) {
                $model->$relationship();
                $statement = $this->getStatement();

                foreach ($model->getStatement()->getJoins() as $join) {
                    $statement->setJoinString($join);
                }
            }
        }
    }

    /**
     * returns distinct values of the related model;
     *
     * @param $relationship
     * @return $this
     */
    public function withOnly($relationship)
    {
        $this->$relationship();

        $table = $this->relatedModel->table;

        $statement = $this->getStatement();

        $statement->distinctWithTable($table);

        return $this;

    }

    /**
     * returns instance of another object via relationships.
     *
     * @param $relationship
     * @return Loupe
     */
    public function morphTo($relationship)
    {
        $this->$relationship();

        $model = $this->relatedModel;

        /**
         * if there's a pivot table we will actually force to
         * become that pivot table object since most pivot tables don't have models;
         */
        if (!is_null($this->pivotTable)) {
            $model->table = $this->pivotTable;
            $model->customTime = true;
        }

        return $this->relatedModel;
    }

    /**
     * @param $relatedModel
     * @param null $foreignKey
     * @param null $localKey
     * @return $this
     */
    public function hasOne($relatedModel, $foreignKey = null, $localKey = null)
    {
        $this->relatedModel = new $relatedModel;

        $this->pushRelationships($this->relatedModel);

        $relatedModelTable = $this->relatedModel->table;
        $this->localKey = !is_null($localKey) ? $localKey : $this->relatedModel->model . '_id';
        $this->foreignKey = !is_null($foreignKey) ? $foreignKey : $this->relatedModel->primaryKey;

        $this->leftJoin($relatedModelTable . '.' . $this->foreignKey, $this->table . '.' . $this->localKey);

        return $this;
    }

    /**
     * @param $relatedModel
     * @param $foreignKey
     * @param null $localKey
     * @return $this
     * @throws ModelException
     */
    public function hasMany($relatedModel, $foreignKey = null, $localKey = null)
    {

        $this->relatedModel = new $relatedModel;

        $this->pushRelationships($this->relatedModel);

        $relatedModelTable = $this->relatedModel->table;
        $this->localKey = !is_null($localKey) ? $localKey : $this->primaryKey;
        $this->foreignKey = !is_null($foreignKey) ? $foreignKey : $this->model . '_id' ;

        $this->join($this->localKey, $this->foreignKey, $relatedModelTable);

        return $this;
    }

    /**
     * @param $relatedModel
     * @param null $foreignKey
     * @param null $localKey
     * @return $this
     */
    public function belongsTo($relatedModel, $foreignKey = null, $localKey = null)
    {

        $this->relatedModel = new $relatedModel;

        $this->pushRelationships($this->relatedModel);

        $this->localKey = !is_null($localKey) ? $localKey : $this->primaryKey;
        $this->foreignKey = !is_null($foreignKey) ? $foreignKey : $this->model . '_id' ;

        $this->rightJoin($this->localKey, $this->relatedModel->table . '.' . $this->foreignKey);

        return $this;

    }

    /**
     * @param $relatedModel
     * @param null $pivotTable
     * @param null $pivotTableLeftKey
     * @param null $pivotTableRightKey
     * @throws ModelException
     *
     * @return $this
     */
    public function belongsToMany(
        $relatedModel,
        $pivotTable = null,
        $pivotTableLeftKey = null,
        $pivotTableRightKey = null) {

        $this->relatedModel = new $relatedModel();

        $this->pushRelationships($this->relatedModel);

        $relatedTable = $this->relatedModel->table;
        $relatedTablePrimaryKey = $this->relatedModel->primaryKey;

        if (is_null($pivotTable)) {
            $arr_tables = [$this->table, $relatedTable];
            sort($arr_tables);
            $this->pivotTable = implode('_', $arr_tables);
        } else {
            $this->pivotTable = $pivotTable;
        }

        $pivotTableLeftKey = is_null($pivotTableLeftKey) ? $this->relatedModel->model . '_id' : $pivotTableLeftKey;
        $pivotTableRightKey = is_null($pivotTableRightKey) ? $this->model . '_id' : $pivotTableRightKey;

        $this->join($this->primaryKey, $pivotTableRightKey, $this->pivotTable)
            ->join($this->pivotTable . '.' . $pivotTableLeftKey,
                $relatedTable  . '.' . $relatedTablePrimaryKey, $relatedTable);

        return $this;
    }

    /**
     * Sets a relationship through a third table
     *
     * @param $relatedModel - the data that we really want.
     * @param $throughModel - The table that we're using to get the data
     * @param $throughModelKey - The column on through table that contains the referenced key
     * @param $throughModelLocalKey - The key in the related model that references the through table.
     *
     * @return $this
     */
    public function hasManyThrough(
        $relatedModel,
        $throughModel,
        $throughModelKey = 'id',
        $throughModelLocalKey = 'id')
    {
        $this->relatedModel = new $relatedModel;
        $table = $this->relatedModel->table;

        $throughModel = new $throughModel();

        $this->pushRelationships($this->relatedModel);

        $this->join($throughModel->primaryKey, $throughModel->table . '.' . $throughModelLocalKey)
            ->join($throughModel->table . '.' . $throughModelKey, $table . '.' . $this->relatedModel->primaryKey);

        return $this;
    }

    /**
     * @param $relatedModel
     */
    public function pushRelationships($relatedModel)
    {
        $this->relationships[] = $relatedModel;
    }

    /**
     * Get connection for this model.
     *
     * @return DatabaseManager
     */
    public function getConnection()
    {
        return Database::getConnection($this->connection);
    }

    /**
     * @param $value
     * @return bool|string
     */
    public static function encrypt($value)
    {
        $hash_cost_factor = defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null;
        $encryptedPass = password_hash($value, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));

        return $encryptedPass;
    }

    /**
     * returns database equivalent name for model.
     *
     * @return string - plural table name
     */
    private function getTableName()
    {
        $class = get_class($this);

        $mem = new Cache();
        if ($tableName = $mem->get($class . '-table-name')) {
            return $tableName;
        }

        $break = explode('\\', $class);
        $ObjectName = end($break);
        $className = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $ObjectName));
        $tableName = Inflect::pluralize($className);

        $mem->add($class . '-table-name', $tableName, 1440);

        return $tableName;
    }

    /**
     * @param array $array
     * @return bool
     */
    public function isAssociative(array $array)
    {
        $i = 0;
        $Associative = false;
        foreach ($array as $k => $record) {
            if ($k != $i) {
                $Associative = true;
            } else {
                $Associative = false;
            }
            $i++;
        }
        return $Associative;
    }

    /**
     *
     * Allows for database queries to be wrapped withing a transaction.
     *
     * @param callable $closure
     * @throws \Exception
     * @return $this
     */
    public function transaction(callable $closure) {

        $con = $this->getConnection();
        $con->beginTransaction();

        try {
            $closure($this);
            $con->commit();
            return $this;

        } catch (\Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     *
     * Allows to run queries against the database without committing anything.
     *
     * @param callable $closure
     * @throws \Exception
     * @return $this
     */
    public function dryRun(callable $closure) {

        $con = $this->getConnection();
        $con->beginTransaction();

        $closure();

        $con->rollBack();
        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->attributes->toArray());
    }
}
