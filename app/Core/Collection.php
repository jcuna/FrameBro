<?php
/**
 * Author: Jon Garcia.
 * Date: 4/13/16
 * Time: 7:07 PM
 */

namespace App\Core;

use App\Core\Interfaces\Arrayable;
use App\Core\Interfaces\Jsonable;
use App\Core\Exceptions\ModelException;

class Collection extends \ArrayIterator implements Arrayable, Jsonable
{

    /**
     * @return mixed
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
     * @return bool
     */
    public function isNotEmpty()
    {
        return ! $this->isEmpty();
    }

    /**
     * @param $key
     * @return bool
     */
    public function keyExist($key): bool
    {
        return parent::offsetExists($key);
    }


    /**
     * @param $item
     */
    public function push($item)
    {
        parent::append($item);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $arrayResponse = [];
        foreach ($this as $item) {
            if ($item instanceof Arrayable) {
                $item = $item->toArray();
            }
            $arrayResponse[] = $item;
        }

        return $arrayResponse;
    }

    /**
     * @param callable $callable
     */
    public function each(callable $callable)
    {
        foreach ($this as $key => $item)
        {
            $callable($item, $key);
        }
    }

    /**
     * @param string $key
     * @param $value
     */
    public function put(string $key, $value)
    {
        parent::offsetSet($key, $value);
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function get($key)
    {
        return parent::offsetExists($key) ? parent::offsetGet($key) : null;
    }

    /**
     * @return array
     */
    public function keys(): array
    {
        return array_keys(parent::getArrayCopy());
    }


    /**
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}