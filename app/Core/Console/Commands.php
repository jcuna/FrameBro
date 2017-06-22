<?php
/**
 * Author: Jon Garcia.
 * Date: 6/3/17
 * Time: 3:37 PM
 */

namespace App\Core\Console;


use App\Core\Collection;
use App\Core\Console\Exceptions\BadCommand;

class Commands
{

    /**
     * @var Collection
     */
    private static $commands;

    /**
     * @var Console;
     */
    private static $scopeConsole;

    /**
     * @param string $signature
     * @param $arg2 -- a closure or a class
     * @param string|null $method
     * @return Command
     */
    public static function do(string $signature, $arg2, string $method = null): Command
    {
        if (! self::$commands instanceof Collection) {
            self::$commands = new Collection();
        }

        $command = new Command($signature);

        if (self::$commands->keyExist($command->getName())) {
            throw new \InvalidArgumentException("Command has already been declared.");
        }

        self::$commands->put($command->getName(), $command);

        if ($arg2 instanceof \Closure) {
            $command->setCallable(\Closure::bind($arg2, self::getScopeConsole()));
        } elseif (is_string($arg2) && class_exists($arg2)) {
            $obj = new $arg2;
            self::validateClass($arg2, $method, $obj);
            $command->setObject($obj);
            $command->setMethod($method);
        } else {
            throw new \InvalidArgumentException("Command doesn't have a valid function or class method");
        }

        return $command;
    }

    /**
     * @return Console
     */
    private static function getScopeConsole(): Console
    {
        if (is_null(self::$scopeConsole)) {
            self::$scopeConsole = new class extends Console {};
        }
        return self::$scopeConsole;
    }

    /**
     * @return Collection
     */
    public static function getCommands(): Collection
    {
        return self::$commands;
    }

    /**
     * @param string $name
     * @return Command|null
     */
    public static function command(string $name)
    {
        return self::$commands[$name] ?? null;
    }

    /**
     * @param $arg2
     * @param string $method
     * @param $obj
     * @throws BadCommand
     */
    private static function validateClass($arg2, string $method, $obj)
    {
        if (!$obj instanceof Console) {
            throw new BadCommand("{$arg2} must extend " . Console::class);
        }
        if (!method_exists($obj, $method)) {
            throw new BadCommand("{$arg2} does not have a method called {$method}");
        }
    }
}