<?php
/**
 * Author: Jon Garcia.
 * Date: 4/13/16
 * Time: 7:07 PM
 */

namespace App\Core\Model;

use App\Core\Collection;
use App\Core\Exceptions\ModelException;

class ModelCollection extends Collection
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
    public function first(): Loupe
    {
        $this->rewind();
        return $this->current();
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
    public function push($model)
    {
        $this->append($model);
    }
}