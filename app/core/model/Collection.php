<?php
/**
 * Author: Jon Garcia.
 * Date: 4/13/16
 * Time: 7:07 PM
 */

namespace App\Core\Model;

use App\Core\Api\Arrayable;
use App\Core\Api\Jsonable;
use App\Core\Exceptions\ModelException;

class Collection extends \ArrayIterator implements Arrayable, Jsonable
{

    /**
     * Collection constructor.
     * @param array $array
     * @throws ModelException
     */
    public function __construct(array $array = [])
    {
        foreach ($array as $model) {
            if (!$model instanceof Loupe) {
                throw new ModelException("One or more items in the collection are invalid models");
            }
        }
        parent::__construct($array);
    }

    /**
     * @return Loupe
     */
    public function first()
    {
        $this->rewind();
        return $this->current();
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() === 0;
    }

    /**
     * @param Loupe $model
     * @throws ModelException
     */
    public function append($model)
    {
        if ($model instanceof Loupe) {
            parent::append($model);
        } else {
            throw new ModelException("Not a valid model object");
        }
    }

    /**
     * @param Loupe $model
     */
    public function push(Loupe $model)
    {
        parent::append($model);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $arrayResponse = [];
        /** @var Loupe $model */
        foreach ($this as $model) {
            $arrayResponse[] = $model->toArray();
        }

        return $arrayResponse;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return Loupe
     * @throws ModelException
     */
    public function current()
    {
        $current = parent::current();

        if ($current instanceof Loupe) {
            return $current;
        }
        throw new ModelException("Invalid type");
    }
}