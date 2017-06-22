<?php
/**
 * Created by Jon Garcia
 */

namespace App\Core\Interfaces;

use App\Core\Model\Loupe;

/**
 * Interface ApiInterface
 * @package App\Sdk\Tools
 */
interface ModelInterface
{

    /**
     * @return string
     */
    public function getSelfModelName();

    /**
     * @param null $id
     * @return $this
     */
    public function find($id, array $projection = []);

    /**
     * @param array $projection
     * @return mixed
     */
    public function all(array $projection = []);

    /**
     * Sets a select distinct query
     * @return $this
     */
    public function distinct();

    /**
     * @param $field
     * @param $binding
     * @param string $comparison
     * @return $this
     */
    public function where($field, $binding, $comparison = "=");

    /**
     * @param $field
     * @param $binding
     * @param string $comparison
     * @return $this
     */
    public function orWhere($field, $binding, $comparison = "=");

    /**
     * @param $field
     * @param $bindings
     * @return $this
     */
    public function WhereIn($field, array $bindings);

    /**
     * @param $field
     * @param $bindingA
     * @param $bindingB
     * @return mixed
     */
    public function between($field, $bindingA, $bindingB);

    /**
     * @param $leftField
     * @param $rightField
     * @param null $rightTable
     * @param string $joinType
     * @return mixed
     */
    public function join($leftField, $rightField, $rightTable = null, $joinType = 'INNER JOIN');

    /**
     * performs left join
     * @param $rightTable
     * @param $leftField
     * @param $rightField
     * @return $this|Loupe
     */
    public function leftJoin($leftField, $rightField, $rightTable = null);

    /**
     * performs right join
     * @param $rightTable
     * @param $leftField
     * @param $rightField
     * @return $this|Loupe
     */
    public function rightJoin($leftField, $rightField, $rightTable = null);

    /**
     * @param null $column
     * @return mixed
     */
    public function count($column = null);

    /**
     * @param array|NULL $attributes
     * @return bool
     * @throws \Exception
     */
    public function save(array $attributes = NULL);

    /**
     * Insert multiple records
     *
     * @param array $records
     * @return bool
     */
    public function insert(array $records);

    /**
     * @throws \Exception
     */
    public function delete();

    /**
     * regardless of the chained position of this method,
     * it will always be appended to the last part of the statement unless there's an offset
     * @param $int Integer
     * @return $this Object
     */
    public function limit($int);

    /**
     * regardless of the chained position of this method,
     * it will always be appended to the last part of the statement
     * @param $int Integer
     * @return $this Object
     */
    public function offset($int);

    /**
     * @param $column Integer
     * @return $this Object
     */
    public function order($column);

    /**
     * @param $field
     * @return $this
     */
    public function groupBy($field);

    /**
     * @param array $fields
     * @return $this
     */
    public function groupConcat(array $fields);

    /**
     * Very specific way to use this. See example below pay attention to quotes
     * $test = new User();;
     * $test->contact("username, '-', first_name, '-', created_at");;
     * @param $concatenatedFields
     * @param null $displayAs
     * @return $this
     */
    public function concat($concatenatedFields, $displayAs = null);

    /**
     * @param $property
     * @return mixed
     */
    public function __get($property);

    /**
     * @param $property
     * @param $value
     */
    public function __set($property, $value);

    /**
     * @param $name
     * @return void
     */
    public function __unset($name);

    /**
     * @param array $fields
     * @return $this
     */
    public function get(array $fields = ['*']);

    /**
     * @return mixed
     */
    public function first();

    /**
     * @param $relationship
     * @return $this
     */
    public function with($relationship);

    /**
     * @param $relationship
     * @return $this - distinct values of the related model;
     */
    public function withOnly($relationship);

    /**
     * returns instance of another object via relationships.
     * @param $relationship
     * @return mixed
     */
    public function morphTo($relationship);

    /**
     * @param $relatedModel
     * @param null $foreignKey
     * @param null $localKey
     * @return $this
     */
    public function hasOne($relatedModel, $foreignKey = null, $localKey = null);

    /**
     * @param array $array
     * @return mixed
     */
    public function isAssociative(array $array);

    /**
     * @param $relatedModel
     * @param null $foreignKey
     * @param null $localKey
     * @return mixed
     */
    public function hasMany($relatedModel, $foreignKey = null, $localKey = null);

    /**
     * @param $relatedModel
     * @param null $foreignKey
     * @param null $localKey
     * @return mixed
     */
    public function belongsTo($relatedModel, $foreignKey = null, $localKey = null);


    /**
     * @param $relatedModel
     * @param null $pivotTable
     * @param null $pivotTableLeftKey
     * @param null $pivotTableRightKey
     * @return mixed
     */
    public function belongsToMany($relatedModel, $pivotTable = NULL, $pivotTableLeftKey = NULL, $pivotTableRightKey = NULL);


    /**
     * @param $relatedModel
     */
    public function pushRelationships($relatedModel);

    /**
     * Sets a relationship through a third table
     * @param $relatedModel - the data that we really want.
     * @param $throughModel - The table that we're using to get the data
     * @param $throughTableKey - The column on through table that contains the referenced key
     * @param $throughTableLocalKey - The key in the related model that references the through table.
     */
    public function hasManyThrough($relatedModel, $throughModel, $throughModelKey = 'id', $throughModelLocalKey = 'id');

    /**
     * @param callable $closure
     * @return mixed
     */
    public function transaction(callable $closure);

    /**
     * @param callable $closure
     * @return mixed
     */
    public function dryRun(callable $closure);

    /**
     * @param $value
     * @return bool|string
     */
    public static function encrypt($value);
}