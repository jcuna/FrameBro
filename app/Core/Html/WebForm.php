<?php
/**
 * Created By: Jon Garcia
 * Date: 1/16/16
 */
namespace App\Core\Html;

use App\Core\Request;
use App\Core\Model\Loupe;
use \App;

/**
 * Class WebForm
 * @package App\Core\Html
 */
class WebForm extends Markup
{
    /**
     * Holds the values from the model
     * @var
     */
    public static $values = array();

    /**
     * Holds the current model sent to the view if any
     *
     * @var \App\Core\Model\Loupe
     */
    private static $model = array();

    /**
     * Holds the fields that have been invalidated by the Validator.
     *
     * @var array
     */
    public static $invalidFields = array();

    /**
     * A simple array index
     *
     * @var int
     */
    private static $index = 0;

    /**
     * Gets values from the model and binds them to
     * WebForm so they can be used to set field values
     *
     * @param $strModel
     * @internal param $Model
     */
    public static function forModel($strModel)
    {
        foreach (self::$model as $objModel) {

            if ($objModel instanceof $strModel && $objModel instanceof Loupe) {
                self::$values = $objModel->toArray();
            }
        }
    }

    /**
     * Sets model bindings
     *
     * @param $model
     */
    public static function modelBinding($model)
    {
        self::$model[] = $model;
    }

    public static function setInvalidFields($field)
    {
        self::$invalidFields[] = $field;
    }

    /**
     * Advances the array index one step.
     */
    public static function advanceIndex()
    {
        self::$index++;
    }

    public static function resetIndex()
    {
        self::$index = 0;
    }


    /**
     * Gets posted data or data from model
     * and sets them as value to the input fields.
     *
     * @param $field
     *
     * @return string
     */
    private static function get($field)
    {

        $toUnderscored = str_replace('-', '_', $field);

        if ( $result = self::getParams($field) ) {

            return $result;

        } elseif ( isset(self::$values[$field]) ) {

            return self::$values[$field];

        } elseif ( isset(self::$values[$toUnderscored]) ) {

            return self::$values[$toUnderscored];
        }

        return '';
    }

    /**
     * @param $field
     * @return string|null
     */
    private static function getParams($field)
    {
        $params = App::getRequest();

        if ( $params->{$field} && $params->{$field} !== '') {

            if (is_array($params->{$field}) && isset($params->{$field}[self::$index])) {
                $value = $params->{$field}[self::$index];
                return $value;
            }

            return $params->{$field};
        }

        if ($pos = strpos($field, '[]')) {

            $parent = substr($field, 0, $pos);
            $key = substr($field, $pos + 2);
            $posBracket = strpos($key, '[');
            $posBracketClose = strpos($key, ']');
            if ($posBracket !== false) {
                $key = substr($key, $posBracket + 1, $posBracketClose - 1);
            }

            if (isset($params->{$parent}[self::$index][$key])) {

                return $params->{$parent}[self::$index][$key];
            }
        }

        return false;
    }

    /**
     * @param $field
     * @return string
     */
    public static function errorClass($field)
    {
        $result = '';
        if (in_array($field, self::$invalidFields)) {
            $result = ' has-error';
        }
        return $result;
    }

    /**
     * @param $name
     * @param null $id
     * @param null $action
     * @param null $class
     * @param bool|false $files
     * @param string $method
     */
    public static function open( $name, $id = null, $action = null, $class = null, $files = false, $method = 'POST' )
    {
        self::setFormAction($action);

        $class = is_null( $class ) ? 'form-block' : $class ;

        $encType = $files === TRUE ? ' enctype="multipart/form-data"' : '';
        $formStart = '';
        $formStart .= '<form name="' . $name . '" action="' . $action . '" method="' . $method . '" ' . $encType . ' accept-charset="utf-8"';
        $formStart .= 'class ="' . $class . '"';
        $formStart .= !is_null($id) ? 'id="' . $id .  '">' : '>';
        echo $formStart;
    }

    /**
     * Set proper form action
     * @param $action
     */
    private static function setFormAction(&$action)
    {
        $url = Request::$uri;

        if ( is_null( $action )) {

            switch ($url) {
                case "AjaxController":
                    $action = self::getAjaxAction();
                    break;
                case "/":
                    $action = "/";
                    break;
                default:
                    $action = "/$url";
            }
        }
    }

    /**
     * Helper method to get the url before initiating ajax call
     *
     * @return string
     */
    private static function getAjaxAction()
    {
        $params = new Request();
        $action = '/';
        if ($params->ajax['url'] !== "/") {
            $action = $action . $params->ajax['url'];
        }
        return $action;
    }

    /**
     * @param $name
     * @param string $type
     * @param array $attributes
     * @param null $label
     * @param null $text
     */
    public static function field($name, $type = 'text', $attributes = array(), $label = null, $text = null)
    {
        $paramsName = preg_replace('/(.*)\[\]$/', '$1', $name );

        $fieldValue = self::get($paramsName);

        //is this a text-list js element?
        if (is_array($fieldValue) && $type !== 'checkbox' ) {
            $hiddenFields = '';
            foreach ( $fieldValue as $k => $v ) {
                $hiddenFields .= '<input type="hidden" name="' . $name.'[]' . '" value="' . $v . '">';
            }
            $fieldValue = '';
        } else { $hiddenFields = ''; }

        if (!isset($attributes['class'])) {
            $attributes['class'] = 'form-control';
        }
        $field = '';
        $field .= !is_null($label) ? '<label for="' . $name . '">' . $label . '</label>' : '';
	    $field .= '<input type="' . $type . '" name="' . $name . '" ';
        $field .= isset($attributes['value']) ? '' : 'value="' . $fieldValue . '"';
        $field .= self::joinAttributes($attributes);

        if ($type === 'radio' || $type === 'checkbox') {
            if ( isset($attributes['value']) && ( $fieldValue == $attributes['value'] ||
                    ( is_array($fieldValue) && in_array($attributes['value'], $fieldValue)))) {
                $field .= 'checked="checked"';
            }
        }
	    $field .= '>' . $text;
        $field .= $hiddenFields;
        echo $field;
    }

    /**
     * @param $name
     * @param array $attributes
     * @param $label
     */
    public static function textarea($name, $attributes = array(), $label)
    {
        if (!isset($attributes['class'])) {
            $attributes['class'] = 'ckeditor';
        }
        $textarea = '';
		$textarea .= '<label for="' . $name . '" class="">' . $label . '</label>';
		$textarea .= '<textarea name="' . $name . '"';
        $textarea .= self::joinAttributes($attributes);
		$textarea .= '>' . self::get($name) . '</textarea>';
        echo $textarea;
    }

    /**
     * @param $name
     * @param array $options
     * @param array $attributes
     * @param null $selected
     * @param null $label
     */
    public static function select($name, array $options, $attributes = array(), $selected = null, $label = NULL)
    {
        $selectedVal = isset($selected) ? $selected : self::get($name);

        if (!isset($attributes['class'])) {
            $attributes['class'] = 'form-control';
        }

        $attributes['class'] .= '';

        if (!isset($attributes['placeholder'])) {
            $attributes['placeholder'] = 'Select an option';
        }

        $selectOptions = '<div class="form-group">';
        $selectOptions .= isset($label) ? '<label for="' . $attributes['name'] . '">' . $label . '</label>' : '';
        $selectOptions .= '<select name="' . $name . '"';

        $selectOptions .= self::joinAttributes($attributes);
        $selectOptions .= '>';

        if ($selectedVal == '') {
            $selectOptions .= '<option value="" selected="selected">' . $attributes['placeholder'] . '</option>';

            foreach ($options as $key => $value) {
                $selectOptions .= '<option value=' . "$key" . '>' . $value . '</option>';
            }
        }
        else {
            $selectOptions .= '<option value="">' . $attributes['placeholder'] . '</option>';
            $selectOptions .= '<option value="' . $selectedVal . '"' . 'selected="selected">' . $options[$selectedVal] . '</option>';

            foreach ($options as $key => $value) {
                if ($value != $options[$selectedVal]) {
                    $selectOptions .= '<option value=' . "$key" . '>' . $value . '</option>';
                }
            }
        }
        $selectOptions .= '></select></div>';
        echo $selectOptions;
    }

    /**
     * @param string $value
     * @param string $attributes
     */
    public static function submit($value = 'Submit', $attributes = null)
    {
        $attr = '';
        $classes = 'btn btn-primary';
        if (is_string($attributes)) {
            $classes = $attributes;
        } elseif (is_array($attributes)) {
            if (isset($attributes['class'])) {
                $classes = $attributes['class'];
                unset($attributes['class']);
            }
            $attr = self::joinAttributes($attributes);
        }

        echo '<button type="submit" class="' . $classes . '"' . $attr . '>' . $value . '</button>';
    }

    /**
     * @param $attributes
     * @return string
     */
    public static function joinAttributes($attributes)
    {
        $string = '';
        foreach($attributes as $attribute => $value) {
            $string .= $attribute . '="' . $value . '"';
        }

        return $string;
    }

    /**
     * Closes form
     */
    public static function close()
    {
        echo '</form>';
    }
}