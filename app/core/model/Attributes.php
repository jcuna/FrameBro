<?php
/**
 * Author: Jon Garcia
 * Date: 1/23/16
 * Time: 12:00 PM
 */

namespace App\Core\Model;

/**
 * Class Attributes
 * @package App\Core\Model
 */
class Attributes implements \IteratorAggregate
{

    /**
     * Attributes constructor.
     * @param null $arAttributes
     */
    public function __construct(array $arAttributes = [])
    {
        $this->setAttributes($arAttributes);
    }

    /**
     * @param $arAttributes
     */
    private function setAttributes($arAttributes)
    {
        foreach ($arAttributes as $prop => $value) {
            $this->{$prop} = $value;
        }
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator() {

        return new \ArrayIterator( $this );

    }

    /**
     * Converts to array.
     *
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array($this);
    }

}