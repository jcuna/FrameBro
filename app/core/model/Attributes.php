<?php
/**
 * Author: Jon Garcia
 * Date: 1/23/16
 * Time: 12:00 PM
 */
declare(strict_types=1);
namespace App\Core\Model;
use App\Core\Api\Arrayable;

/**
 * Class Attributes
 * @package App\Core\Model
 */
class Attributes implements \IteratorAggregate, Arrayable
{

    /**
     * Attributes constructor.
     * @param array $arAttributes
     */
    public function __construct(array $arAttributes = [])
    {
        $this->setAttributes($arAttributes);
    }

    /**
     * @param $arAttributes
     */
    private function setAttributes(array $arAttributes)
    {
        foreach ($arAttributes as $prop => $value) {
            $this->{$prop} = $value;
        }
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this);
    }

    /**
     * Converts to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }
}