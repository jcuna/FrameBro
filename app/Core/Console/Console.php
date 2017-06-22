<?php
/**
 * Author: Jon Garcia.
 * Date: 6/13/17
 * Time: 8:23 PM
 */

namespace App\Core\Console;


abstract class Console
{
    /**
     * @var string
     */
    private $padding = "";

    /**
     * Apply padding to output text
     */
    const PADDING = "  ";

    /**
     * Redirects stdout to /dev/null
     */
    const SUPPRESS_STDOUT = 1;

    /**
     * Return stdout
     */
    const RETURN_STDOUT = 0;


    /**
     * Run command async in the background
     */
    const RUN_IN_BACKGROUND = 2;

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
     * @param $string
     * @param string $color
     * @param bool $autoLine
     */
    public function output($string, $color = 'green', $autoLine = true)
    {
        $str = "\033[" . self::$colors[$color] . "m" . $string . "\033[0m";
        $output = "{$this->padding}{$str}";

        if ($autoLine) {
            $output .= PHP_EOL;
        }
        echo $output;
    }

    /**
     *
     */
    public function increasePadding()
    {
        $this->padding .= self::PADDING;
    }

    /**
     *
     */
    public function resetPadding()
    {
        $this->padding = "";
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
     * @param string $string
     */
    public function info(string $string)
    {
        $this->output($string, "cyan");
    }

    /**
     * @param string $string
     */
    public function error(string $string)
    {
        $this->output($string, "red");
    }

    /**
     * @param string $string
     */
    public function success(string $string)
    {
        $this->output($string, "green");
    }

    /**
     * @param string $string
     */
    public function line(string $string)
    {
        $this->output($string, "white");
    }

    /**
     * Allow you to call another command as if you were using the command line interface
     * @param string $command
     */
    public function call(string $command)
    {
        $pieces = explode(" ", $command);
        Cli::getInstance()->setArgs($pieces, count($pieces));
        Cli::getInstance()->handleCall();
    }

}