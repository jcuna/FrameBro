<?php

/**
 * the auto-loading function, which will be called every time a file "is missing"
 * NOTE: don't get confused, this is not "__autoload", the now deprecated function
 * The PHP Framework Interoperability Group (@see https://github.com/php-fig/fig-standards) recommends using a
 * standardized auto-loader https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md, so we do:
 *
 * @param $class string the class to load.
 *
 * Class Autoload
 */
class Autoload
{
    /**
     * Holds the class with namespace
     *
     * @var string
     */
    private $class;

    /**
     * Holds the class as sent via params.
     *
     * @var mixed
     */
    private $classParams;

    /**
     * Autoload constructor.
     * @param $class
     */
    function __construct($class)
    {

        $dirs = explode('\\', $class);
        $this->classParams = array_pop($dirs);
        $path = strtolower(implode(DIRECTORY_SEPARATOR, $dirs));
        $this->class = ABSOLUTE_PATH . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $this->classParams . '.php';

        self::autoload();
    }

    /**
     * Autoload method.
     */
    public function autoload()
    {
        if (file_exists($this->class)) {
            require_once($this->class);
        } elseif (file_exists(CORE_PATH . $this->classParams . ".php")) {
            require_once CORE_PATH . $this->classParams . ".php";
        }
    }
}

/**
 * class the autoload class.
 * @param $class
 */
function autoload($class) {
    new Autoload($class);
}


// spl_autoload_register defines the function that is called every time a file is missing. as we created this
// function above, every time a file is needed, autoload(THENEEDEDCLASS) is called
spl_autoload_register("autoload");