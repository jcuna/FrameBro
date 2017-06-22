<?php
/**
 * Author: Jon Garcia.
 * Date: 6/6/17
 * Time: 6:59 PM
 */

namespace App\Core\Console;


use App\Core\Arguments;
use App\Core\Console\Exceptions\BadCommand;
use App\Core\Interfaces\HasArguments;

class Argv implements HasArguments
{
    /**
     * @var Arguments
     */
    private $arguments;

    /**
     * @var bool
     */
    private $empty = true;

    /**
     * @var string
     */
    private $command;

    /**
     * @var array
     */
    private $passedArgs = [];


    public function __construct(array $args, int $argCount)
    {
        if ($argCount > 0) {
            $this->processCommand($args, $argCount);
            $this->empty = $argCount === 0;
            $this->passedArgs = array_values($args);
        } else {
            $this->command = "help";
        }
    }


    /**
     * @return string
     */
    public function command(): string
    {
        return $this->command;
    }

    /**
     * @param $args
     * @param $argCount
     * @throws BadCommand
     */
    private function processCommand(&$args, &$argCount)
    {
        $this->command = $args[0];
        unset($args[0]);
        $argCount--;
    }

    /**
     * @param $command
     * @throws BadCommand
     */
    public function processArgs(Command $command)
    {
        $matched = [];
        $required = [];
        $allArgs = [];
        $associatedArgs = [];
        $shouldSkip = false;
        foreach ($command->getParameters() as $params) {
            $allArgs[] = $params["original"];
            $this->addIfRequired($required, $params);
            foreach ($this->passedArgs as $k => $arg) {
                if ($shouldSkip) {
                    $shouldSkip = ! $shouldSkip;
                    continue 2;
                }
                if ($params["type"] === "option" && strpos($arg, $params["name"]) === 2) {
                    $this->processOption($params, $k, $associatedArgs, $arg, $matched, $shouldSkip);
                } elseif (! in_array($arg, $matched) && !isset($associatedArgs[$params["name"]])
                    && $params["type"] === "argument") {
                    $associatedArgs[$params["name"]] = $arg;
                    $matched[] = $arg;
                }
            }
        };
        $this->validateArguments($required, $matched, array_keys($associatedArgs), $allArgs);
        $this->arguments = new Arguments($associatedArgs);
    }

    /**
     * @param array $requiredArgs
     * @param array $matchedArgs
     * @throws BadCommand
     */
    private function validateArguments(array $requiredArgs, array $matchedArgs, array $keysMatched, $allArgs)
    {
        if (in_array("--help", $matchedArgs) && in_array("--help", $allArgs)) {
            return;
        }
        $extraParams = array_diff($this->passedArgs, $matchedArgs);
        $missingParams = array_diff($requiredArgs, $keysMatched);
        $countExtraParams = count($extraParams);
        $countRequired = count($missingParams);
        if ($countExtraParams > 0) {
            $verb = $countExtraParams === 1 ? "argument" : "arguments";
            throw new BadCommand("Unexpected {$verb} " . implode(", ", $extraParams));
        }

        if ($countRequired> 0) {
            $verb = $countRequired === 1 ? "is" : "are";
            throw new BadCommand(implode(", ", $missingParams) . " {$verb} required");
        }
    }

    /**
     * @param array $required
     * @param array $param
     */
    private function addIfRequired(array &$required, array $param)
    {
        if ($param["required"]) {
            $required[] = $param["name"];
        }
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->empty;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value)
    {
        // TODO: Implement __set() method.
    }

    /**
     * @param string $name
     * @return null
     */
    public function __get(string $name)
    {
       return $this->get($name);
    }

    /**
     * @param string $name
     * @return null
     */
    public function get(string $name)
    {
        if (isset($this->arguments->{$name})) {
            return $this->arguments->{$name};
        }

        return null;
    }

    /**
     * @return Arguments
     */
    public function arguments(): Arguments
    {
        return $this->arguments;
    }

    /**
     * @param array $params
     * @param int $index
     * @param array $associatedArgs
     * @param string $arg
     * @param $matched
     * @param bool $shouldSkip
     * @return void
     * @throws BadCommand
     */
    private function processOption(array $params, int $index, array &$associatedArgs, string $arg, &$matched, bool &$shouldSkip)
    {
        if ($params["required"]) {
            if (!isset($this->passedArgs[$index + 1])) {
                throw new BadCommand("{$params["name"]} requires a value");
            }
            $associatedArgs[$params["name"]] = $this->passedArgs[$index + 1];
            $shouldSkip = true;
            $matched[] = $arg;
            $matched[] = $this->passedArgs[$index + 1];
        } else {
            $associatedArgs[$params["name"]] = true;
            $matched[] = $arg;
        }
    }
}