<?php
/**
 * Author: Jon Garcia
 * Date: 5/29/16
 */

namespace App\Core;

use App\Core\Http\Routes;
use App\Core\Migrations\Migrations;

class Cli
{
    /**
     * CLI font colors
     * @var array
     */
    public static $colors = [

        // Set up shell colors
        'black'        => '0;30',
        'dark_gray'    => '1;30',
        'blue'         => '0;34',
        'light_blue'   => '1;34',
        'green'        => '0;32',
        'light_green'  => '1;32',
        'cyan'         => '0;36',
        'light_cyan'   => '1;36',
        'red'          => '0;31',
        'light_red'    => '1;31',
        'purple'       => '0;35',
        'light_purple' => '1;35',
        'brown'        => '0;33',
        'yellow'       => '1;33',
        'light_gray'   => '0;37',
        'white'        => '1;37'
    ];

    /**
     * Cli background colors
     * @var array
     */
    public static $background = [

        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'light_gray' => '47'
    ];

    /**
     * The calling script
     *
     * @var string
     */
    private $script;

    /**
     * The method to run
     *
     * @var string
     */
    private $method;

    /**
     * The arguments sent to the method
     *
     * @var array
     */
    private $args = [];

    /**
     * The directory where migrations live
     *
     * @var string
     */
    private $migrationsDir = MIGRATIONS_PATH;

    /**
     * List of registered commands
     *
     * @var array
     */
    private $commands = [

        "flush:views" => 'flushViews',
        "rollback" => "rollBack",
        "db:migrate" => "migrate"
    ];

    /**
     * Cli constructor.
     * @param $args
     */
    public function __construct($args)
    {

        $this->verifyArgs($args);

        $this->assignArguments($args);

        return $this->callAction();

    }

    /**
     * @param $string
     * @param string $color
     */
    public function output($string, $color = 'green', $autoLine = true)
    {
        $str = "\033[" . self::$colors[$color] . "m" . $string . "\033[0m";

        if ($autoLine) {

            echo  $str . PHP_EOL;

        }
    }

    /**
     * Ask user for input
     *
     * @param string $question
     * @return string - the user input
     */
    public function ask($question)
    {
        $this->output($question);
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);

        return trim($line);
    }

    /**
     *
     * @param $command
     * @param $method
     */
    public function registerCommand($command, $method)
    {
        $this->commands[$command] = $method;
    }

    /**
     * @param $command
     * @return bool
     */
    public function getCommand($command)
    {
        if (isset($this->commands[$command]))
        {
            return $this->commands[$command];
        }

        return false;
    }

    /**
     * @param $args
     */
    private function assignArguments($args)
    {
        $this->script = $args[0];

        $this->method = $args[1];

        unset($args[0], $args[1]);

        $this->args = array_values($args);
    }


    /**
     * @param $args
     */
    private function verifyArgs($args) {

        if (!isset($args[1])) {
            $this->output('You must specify a command', 'red');
            foreach ($this->commands as $command => $method) {
                $this->output($command, 'green');
            }
            exit;
        }
    }

    /**
     * @return bool
     */
    private function callAction()
    {
        try {
            $method = $this->method;
            if ($this->getCommand($method)) {
                $method = $this->getCommand($method);
            }

            if (!method_exists($this, $method)) {
                throw new \Exception('Method ' . $method . ' does not exist');
            }
            if (empty($this->args)) {

                $this->$method();

            } else {
                return call_user_func_array([$this, $method], [$this->args]);
            }
        } catch (\Exception $e) {
            $this->output($this->getOutputFromException($e), 'red');
        }
        return false;
    }

    /**
     * @param \Exception $e
     * @return string
     */
    public function getOutputFromException(\Exception $e)
    {
        $output = "Error: " . $e->getCode() . " " . $e->getMessage();
        $output .= PHP_EOL . "Line: " . $e->getLine();
        $output .= PHP_EOL . "File: " . $e->getFile();

        return $output;
    }

    /**
     * Shows routes
     */
    public function routes()
    {
        $patterns = [
            '@.*\s+\[|\]|string|\(\d+\)@',
            '@\n{2}@',
            '@Routes::getRoutes\(\)@',
            '@Called from\s\+\d+\s[a-z\/\.]+\.php@i'
        ];

        $replacements = [
            "",
            "\n",
            " FrameBro Framework",
            ""
        ];

        echo preg_replace($patterns, $replacements , @\Kint::dump(Routes::getRoutes()));

    }

    /**
     * Run migrations
     *
     * @throws \Exception
     */
    public function migrate($down = false)
    {
        $files = getDirectoryFiles($this->migrationsDir);

        foreach ($files as $file) {

            if (strpos($file, 'migration') > 0) {

                $namespace = '\\App\\Migrations\\';
                $className =  basename($file, '.php');

                $class = $namespace . $className;

                if (class_exists($class)) {

                    try {
                        /** @var Migrations $response */
                        $response = new $class($down);

                        $time = $response->getElapsedTimeSum();

                        $this->output("$className OK time: $time milliseconds", 'green');

                    } catch (\Exception $e) {

                        $func = $e->getTrace()[2]['function'];

                        $migration = basename($e->getTrace()[2]['file'], '.php');

                        $output = "Failed migrating $migration on $func" . PHP_EOL;
                        $output .= "Error: " . $e->getCode() . " " . $e->getMessage();

                        $this->output($output, 'red');
                    }

                } else {

                    continue;
                }
            }
        }
    }

    /**
     * rollback migrations
     */
    public function rollBack()
    {
        $this->migrate(true);
    }

    /**
     * Deletes template files.
     */
    public function flushViews()
    {
        $files = glob(STORAGE_PATH .'views/*');
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }
    }
}