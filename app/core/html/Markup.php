<?php
/**
 * Author: Jon Garcia
 * Date: 1/18/16
 */

namespace App\Core\Html;

class Markup
{
    /**
     * The html tag
     *
     * @var string
     */
    public static $tag;

    /**
     * @param $tag
     * @param array|null $attributes
     * @param string $text
     */
    public static function element($tag, array $attributes = null, $text = '')
    {
        $singletonTags = [ 'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source' ];
        $closing = in_array($tag, $singletonTags) ? ">" : ">$text</" . $tag . ">";

        $element = '<' . $tag;
        if (!empty($attributes)) {
            $element .= ' ';
            foreach ($attributes as $attribute => $value) {
                $element .= $attribute . '="' . $value . '" ';
            }
        }
        $element .= $closing;
        echo $element;
    }

    /**
     * @param string $tag
     * @param string $class
     * @param array $attributes
     */
    public static function before($tag = 'div', $class = 'form-group', array $attributes = array())
    {
        self::$tag = $tag;
        $attrs = '';
        foreach($attributes as $attribute => $value ) {
            $attrs .= $attribute . '="' . $value . '"';
        }
        echo '<' . $tag . ' class="' . $class . '" ' . $attrs  . '>';
    }

    /**
     * @param null $tag
     */
    public static function after($tag = null)
    {
        if (is_null($tag)) {
            echo '</' . self::$tag . '>';
        }
        else echo '</' . $tag . '>';

    }
}