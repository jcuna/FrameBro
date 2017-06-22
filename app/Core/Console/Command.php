<?php
/**
 * Author: Jon Garcia.
 * Date: 6/6/17
 * Time: 11:33 PM
 */

namespace App\Core\Console;

use App\Core\Collection;

class Command
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Collection
     */
    private $parameters;

    /**
     * @var \Closure
     */
    private $callable;

    /**
     * @var Console
     */
    private $object;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $description;

    /**
     * Command constructor.
     * @param $signature
     */
    public function __construct($signature)
    {
        $parts = explode(" ", $signature);
        $this->name = $parts[0];
        $this->validateCommandName($this->name, "command");
        unset($parts[0]);
        $this->parameters = $this->processArgs(array_values($parts));
    }

    /**
     * @param array $args
     * @return Collection
     */
    private function processArgs(array $args): Collection
    {
        $collection = new Collection();
        foreach ($args as $arg) {
            $value = [];
            $value["original"] = rtrim(strtolower($arg), "=null");
            $value["name"] = ltrim($value["original"], "--");
            $value["type"] = "argument";
            $value["required"] = false;

            $this->validateCommandName($value["name"], "argument");

            if (strpos($arg, "=") > 0 && strpos(strtolower($arg),"=null") === false) {
                $value["required"] = true;
            }
            if (strpos($arg, "--") === 0) {
                $value["type"] = "option";
            }
            $collection->push($value);
        }
        return $collection;
    }

    /**
     * @param $name
     * @param $type
     */
    private function validateCommandName($name, $type)
    {
        if (! preg_match('/^[a-zA-Z0-9._\:]+$/', $name)) {
            throw new \InvalidArgumentException("$name is not a valid $type");
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }

    /**
     * @return \Closure
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @param \Closure $callable
     */
    public function setCallable(\Closure $callable)
    {
        $this->callable = $callable;
    }


    /**
     * @return Console
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param Console $object
     */
    public function setObject(Console $object)
    {
        $this->object = $object;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
    }


    /**
     * @param string $description
     */
    public function description(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}