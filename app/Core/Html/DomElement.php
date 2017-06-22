<?php
/**
 * Author: Jon Garcia.
 * Date: 8/9/16
 * Time: 10:19 PM
 */

namespace App\Core\Html;


use App\Core\Interfaces\Arrayable;
use App\Core\Exceptions\AppException;

class DomElement implements Arrayable
{
    /**
     * @var string
     */
    private $error;

    /**
     * DomElement constructor.
     * @param null $element
     * @throws AppException
     */
    public function __construct($element = null)
    {
        if (!class_exists("\\SimpleXMLElement")) {
            throw new AppException("SimpleXMLElement class not available.
            Please install he simpleXml library.");
        }

        if (!is_null($element)) {
            try {
                $this->setAttributes($element);
            } catch(\Exception $e) {
                $this->error = $e->getMessage();
            }
        }
    }

    /**
     * @param $element
     * @throws AppException
     */
    private function setAttributes($element)
    {
        $xml = new \SimpleXMLElement($element);

        $this->text = (string)$xml;

        foreach ($xml[0]->attributes() as $attr => $val) {
            $this->{$attr} = (string)$val;
        }
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function has($attribute)
    {
        return isset($this->{$attribute});
    }

    /**
     * @param $attribute
     * @return mixed
     */
    public function get($attribute) {
        return $this->{$attribute};
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array(new \ArrayIterator($this));
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}