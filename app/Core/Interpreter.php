<?php
/**
 * Author: Jon Garcia
 * Date: 2/11/16
 * Time: 3:39 PM
 */

namespace App\Core;

use \App;

/**
 * TODO Add lexer and extend to a real parser.
 */
class Interpreter
{
    /**
     * @var Interpreter reference to singleton instance of this class
     */
    private static $instance;

    /**
     * @var array
     */
    protected static $viewPatterns = [
        "/@partial\([\"'](.*|.*)['\"]\)/",
        '/@render_feedback@/'
    ];

    /**
     * @var array
     */
    protected static $viewReplacements = [
        '<?php include "$1" ?>',
        '<?php self::renderFeedbackMessages(); ?>'
    ];

    /**
     * @var array
     */
    protected static $layoutPatterns = [
        '/@header_includes@/',
        '/@render_feedback@/'
    ];

    /**
     * @var array
     */
    protected static $layoutReplacements = [
        '<?php require_once(CORE_PATH . "view_helpers/bro.settings.php"); ?>',
        '<?php self::renderFeedbackMessages(); ?>'
    ];

    /**
     * @var array
     */
    protected static $viewPartials;

    /**
     * private constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    private function __construct()
    {
        $aliases = App::getSettings("aliases");
        /** we change class name for full qualified class name and save that template. */
        foreach ($aliases as $alias => $namespace) {
            self::$layoutPatterns[] = self::$viewPatterns[] = "/$alias::/";
            self::$layoutReplacements[] = self::$viewReplacements[] = "$namespace::";
        }
    }

    /**
     * Setup object if not yet setup
     * 
     * @return Interpreter
     */
    private static function bootIfNotBooted()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
    }

    /**
     *
     * allows the insertion of custom replacement patterns.
     * if the third attribute is true, it matches object calls with scope resolution operators
     * if the pattern or replacement is already declared, it will get overwritten
     *
     * @param $pattern
     * @param $replacement
     * @param bool $class
     */
    public static function extendInterpreter($pattern, $replacement, $class = false )
    {
        if ($class) {
            $pattern = "/$pattern::/";
            $replacement = "$replacement::";
        } else {
            $pattern = "/$pattern/";
            $replacement = "$replacement";
        }
        
        self::bootIfNotBooted();

        if(($key = array_search($pattern, self::$viewPatterns)) !== false) {
            unset(static::$viewPatterns[$key]);
            unset(static::$viewReplacements[$key]);
        }

        self::$viewPatterns[] = $pattern;
        self::$viewReplacements[] = $replacement;
    }

    /**
     * @param $file
     * @param $ajax bool
     * parses a view file
     * @return string parsed file;
     */
    public static function parseView($file, $ajax = false)
    {
        self::bootIfNotBooted();

        if ( $ajax ) {
            $prepend = "@render_feedback@";
            $file = "$prepend\n" . $file;
        }

        return preg_replace(self::$viewPatterns, self::$viewReplacements, $file);
    }

    /**
     * @param $file
     * @param $yield
     * parses a master or layout file, second argument is the value of $yield
     * @return string parsed file;
     */
    public static function parseLayout($file, $yield ) {
        self::bootIfNotBooted();
        self::$layoutPatterns[] = '/@yield/';
        self::$layoutReplacements[] = $yield ;

        return preg_replace( self::$layoutPatterns, self::$layoutReplacements, $file );
    }

    /**
     * @param $file
     * @return bool
     */
    public static function hasPartials($file) {
        self::bootIfNotBooted();
        self::$viewPartials = [];
        if (preg_match_all( "/@partial\([\"'](.*|.*)['\"]\)/", $file, $matches)) {
            foreach ( $matches[1] as $match ) {
                self::$viewPartials[] = str_replace('.', '/', $match);
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
        return self::$viewPartials;
    }
}