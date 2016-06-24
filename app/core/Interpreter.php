<?php
/**
 * Author: Jon Garcia
 * Date: 2/11/16
 * Time: 3:39 PM
 */

namespace App\Core;

/**
 * TODO Add lexer and extend to a real parser.
 */
class Interpreter
{
    /**
     * @var Interpreter The reference to *Singleton* instance of this class
     */
    private static $instance;

    protected static $viewPatterns = array(
        "/@partial\([\"'](.*|.*)['\"]\)/",
        '/@render_feedback@/'
    );

    protected static $viewReplacements = array(
        '<?php include "$1" ?>',
        '<?php self::renderFeedbackMessages(); ?>'
    );

    protected static $layoutPatterns = array(
        '/@header_includes@/',
        '/@render_feedback@/'
    );

    protected static $layoutReplacements = array(
        '<?php require_once(CORE_PATH . "view_helpers/bro.settings.php"); ?>',
        '<?php self::renderFeedbackMessages(); ?>'
    );

    protected static $viewPartials;

    /**
     * private constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    private function __construct()
    {
        $aliases = include ABSOLUTE_PATH . '/app/config/aliases.php';
        /** we change class name for full qualified class name and save that template. */
        foreach ($aliases as $alias => $namespace) {
            self::$layoutPatterns[] = self::$viewPatterns[] = "/$alias::/";
            self::$layoutReplacements[] = self::$viewReplacements[] = "$namespace::";
        }
    }

    /**
     * Returns the *Singleton* instance of this class.
     * @return Interpreter The *Singleton* instance.
     */
    private static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param $pattern array
     * @param $replacement array
     * @param $class bool
     * allows the insertion of custom replacement patterns.
     * if the third attribute is true, it matches object calls with scope resolution operators
     * if the pattern or replacement is already declared, it will get overwritten
     */
    public static function extendInterpreter($pattern, $replacement, $class = false )
    {
        if ( $class ) {
            $pattern = "/$pattern::/";
            $replacement = "$replacement::";
        } else {
            $pattern = "/$pattern/";
            $replacement = "$replacement";
        }
        $instance = self::getInstance();

        if(($key = array_search($pattern, $instance::$viewPatterns)) !== false) {
            unset($instance::$viewPatterns[$key]);
            unset($instance::$viewReplacements[$key]);
        }

        $instance::$viewPatterns[] = $pattern;
        $instance::$viewReplacements[] = $replacement;
    }

    /**
     * @param $file
     * @param $ajax bool
     * parses a view file
     * @return string parsed file;
     */
    public static function parseView($file, $ajax = false)
    {
        $instance = self::getInstance();

        if ( $ajax ) {
            $prepend = "@render_feedback@";
            $file = "$prepend\n" . $file;
        }

        return preg_replace($instance::$viewPatterns, $instance::$viewReplacements, $file);
    }

    /**
     * @param $file
     * @param $yield
     * parses a master or layout file, second argument is the value of $yield
     * @return string parsed file;
     */
    public static function parseLayout($file, $yield ) {
        $instance = self::getInstance();
        $instance::$layoutPatterns[] = '/@yield/';
        $instance::$layoutReplacements[] = $yield ;

        return preg_replace( $instance::$layoutPatterns, $instance::$layoutReplacements, $file );

    }

    /**
     * @param $file
     * @return bool
     */
    public static function hasPartials($file) {
        $instance = self::getInstance();
        $instance::$viewPartials = array();
        if (preg_match_all( "/@partial\([\"'](.*|.*)['\"]\)/", $file, $matches)) {
            foreach ( $matches[1] as $match ) {
                $instance::$viewPartials[] = str_replace('.', '/', $match);
            }
            return true;
        }
        return false;
    }

    /**
     * @method Interpreter::getPartials is called after hasPartials to return the found partials.
     * @return array
     */
    public static function getPartials() {
        $instance = self::getInstance();
        return $instance::$viewPartials;
    }
}