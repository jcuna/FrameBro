<?php
/**
 * User: Jon Garcia
 * Date: 1/17/16
 */

namespace App\Core;

use App\Core\Exceptions\ControllerException;
use App\Core\Html\WebForm;
use App\Core\Http\View;
use App\Core\Interfaces\HasArguments;
use App\Core\Model\Generic;

/**
 * Class Validator
 * @package App\Core\Html
 */
trait Validator
{
    /**
     * The request data
     *
     * @var Arguments
     */
    private $request;

    /**
     * Weather the data has been validated
     *
     * @var bool
     */
    public $validated;

    /**
     * If custom validation message
     *
     * @var array
     */
    private $customMessage = [];

    /**
     * The list of fields that are validatable
     * 
     * @var array
     */
    public $validatable = [];

    /**
     * The list of fields containing invalid data
     *
     * @var array
     */
    private $invalidFields = [];

    /**
     * All error messages thrown.
     *
     * @var array
     */
    public $errors = [];

    /**
     * List of all valid validation methods.
     *
     * @var array
     */
    private $methods = [
        'required',
        'any',
        'date',
        'email',
        'isArray',
        'array',
        'bool',
        'boolean',
        'isBool',
        'minimum',
        'number',
        'numeric',
        'integer',
        'phone',
        'regex',
        'requiredWithout',
        'sameAs',
        'unique',
        'whenPresent'
    ];

    /**
     * List of methods that require data to not be empty or unset.
     *
     * @var array
     */
    private $requiredMethods = [
        'required',
        'requiredWithout'
    ];

    /**
     * Holds validations that are depending on field value's presence.
     *
     * @var array
     */
    private $validatePresence = [
        'wherePresent'
    ];

    /**
     * Holds keys to access an array.
     *
     * @var array
     */
    private $arrayAccessor = [];

    /**
     * @param HasArguments $params
     * @param array $data
     */
    public function validate(HasArguments $params, array $data)
    {
        if ($params->isEmpty()) {
            return;
        }
        $this->validated = true;
        $this->request = $params->arguments();

        $this->breakdownValidations($data);
    }

    /**
     * Breaks down all validations by interpreting sent in array.
     *
     * @param $data
     * @throws ControllerException
     */
    private function breakdownValidations($data)
    {
        foreach ($data as $field => $rule) {

            $this->setCustomMessage($rule, $field);

            foreach ($rule as $callable) {
                if (strpos($callable, ':')) {
                    $this->getArrayAccessor($field);
                    $asArray = $this->getFieldsAndCallable($callable, $field, function ($asArray) {
                        return $asArray;
                    });
                    if ($this->notRequiredAndEmptyOrUnset($callable, $field)) {
                        continue;
                    }
                    $this->caller($callable, $asArray);

                } else {

                    if (!$this->methodExists($callable)) {
                        throw new ControllerException("$callable is not a valid validation method");
                    }
                    $this->validatable[$field] = $field;
                    if ($this->notRequiredAndEmptyOrUnset($callable, $field)) {
                        continue;
                    }
                    $this->getArrayAccessor($field);
                    $this->caller($callable, $field);

                }
            }
        }
    }

    /**
     * @param $callable
     * @param $field
     * @param $closure
     * @return mixed
     * @throws ControllerException
     */
    private function getFieldsAndCallable(&$callable, $field, $closure)
    {
        $split = explode(':', $callable);
        $callable = $split[0];

        if (!$this->methodExists($callable)) {
            throw new ControllerException("$callable is not a valid validation method");
        }

        unset($split[0]);
        $this->validatable[$field] = $field;
        $asArray = [];
        $asArray[] = $field;

        foreach ($split as $arg) {
            $asArray[] = $arg;
        }

        return $closure($asArray);
    }

    /**
     * If validating fields within an array
     *
     * @param $arrayAccessor
     * @internal param $fields
     * @internal param $callable
     */
    private function getArrayAccessor($arrayAccessor)
    {
        //reset arrayAccessor
        $this->arrayAccessor = [];

        if (strpos($arrayAccessor, '.*.') > 0) {

            $arrayAccessor = explode('.*.', $arrayAccessor);
            $parent = $arrayAccessor[0];
            $field = $arrayAccessor[1];

            if (isset($this->request->{$parent}) && is_array($this->request->{$parent})) {
                foreach ($this->request->{$parent} as $k => $array ) {

                    foreach ($array as $key => $value) {
                        if ($key === $field) {
                            $this->arrayAccessor = [ $parent, $k, $key
                            ];
                        }
                    }
                }
            }
        }
    }

    /**
     * Sets custom messages
     *
     * @param $rule
     * @param $field
     */
    private function setCustomMessage(&$rule, $field)
    {
        if (isset($rule['message'])) {
            $this->customMessage[$field] = $rule['message'];
            unset($rule['message']);
        }
    }

    /**
     * Checks truthiness of various conditions.
     *
     * @param $callable
     * @param $field
     * @return bool
     */
    private function notRequiredAndEmptyOrUnset($callable, $field)
    {
        $condition1 = !$this->isRequiredMethod($callable)
            && isset($this->request->{$field})
            && $this->request->{$field} == "";

        $condition2 = !$this->isRequiredMethod($callable)
            && !isset($this->request->{$field})
            && !$this->validatePresence($callable);

        return ($condition1 || $condition2);

    }

    /**
     * @param $callable
     * @return bool
     */
    private function methodExists($callable)
    {
        return in_array($callable, $this->methods);
    }

    /**
     * @param $callable
     * @return bool
     */
    private function isRequiredMethod($callable)
    {
        return in_array($callable, $this->requiredMethods);
    }

    /**
     * @param $callable
     * @return bool
     */
    private function validatePresence($callable)
    {

        return in_array($callable, $this->validatePresence);
    }

    /**
     * @param $field
     * @throws ControllerException
     */
    private function notArray($field)
    {
        if (is_array($field)) {
            throw new ControllerException('Invalid field type for ' . __FUNCTION__ . '. Array');
        }
    }

    /**
     * Get field value
     *
     * @param $field
     * @return mixed
     */
    private function getFieldValue($field)
    {
        if (!empty($this->arrayAccessor)) {
            list($parent, $index, $key) = $this->arrayAccessor;
            return $this->request->{$parent}[$index][$key];
        } else {
            return $this->request->{$field};
        }
    }

    /**
     * If field isset
     *
     * @param $field
     * @return bool
     */
    private function fieldIsset($field)
    {
        if (!empty($this->arrayAccessor)) {

            list($parent, $index, $key) = $this->arrayAccessor;
            return isset($this->request->{$parent}[$index][$key]);

        } else {
            return isset($this->request->{$field});
        }

    }

    /**
     * Helper method, checks first item of array to see if it's empty.
     *
     * @param $field
     * @return bool
     */
    private function isThoroughlyEmpty($field)
    {
        if (is_array($field)) {
            if (empty(reset($field))) {
                return true;
            }
        }

        if (empty($field) || $field === '') {
            return true;
        }

        return false;
    }


    /**
     * @param $field
     * use 'field' => ['required']
     */
    protected function required($field)
    {

        if ( !$this->fieldIsset($field) || $this->fieldIsset($field)
            && $this->isThoroughlyEmpty($this->getFieldValue($field))) {
            $this->errors[$field] = 'is required' ;
            $this->validated = false;
            WebForm::setInvalidFields($field);
        }
    }

    /**
     * @param array $field
     * use 'field' => ['minimum:8']
     */
    protected function minimum($field)
    {
        $minimum = (int) $field[1];
        if (strlen($this->getFieldValue($field[0])) < $minimum) {
            $this->errors[$field[0]] = ' requires at least ' . $minimum . ' characters';
            $this->validated = false;
            WebForm::setInvalidFields($field[0]);
        }
    }

    /**
     * @param array $field
     * use 'field' => ['sameAs:anotherField']
     */
    protected function sameAs(array $field)
    {
        if ($this->getFieldValue($field[0]) !== $this->getFieldValue($field[1])) {
            $this->errors[$field[0]] = 'must be equal to ' . self::keyToName($field[1]);
            $this->validated = false;
            WebForm::setInvalidFields($field[0]);
        }
    }

    /**
     * @param $field
     * use 'field' => ['email']
     */
    protected function email($field)
    {
        $this->notArray($field);

        if (!filter_var($this->getFieldValue($field), FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'is not a valid email address';
            $this->validated = false;
            WebForm::setInvalidFields($field);
        }
    }

    /**
     * @param array $validationData
     * use 'field' => ['unique:table:column'] //if column is not sent, the field name will be used
     * @return bool
     */
    protected function unique(array $validationData)
    {
        $column = (isset($validationData[2])) ? $validationData[2] : $validationData[0];
        $value = $this->getFieldValue($column);
        $query = new Generic($validationData[1]);
        $query->where($column, $value)->get([$column]);
        if ($query->count !== 0) {
            $this->errors[$validationData[0]] =  'already exists';
            $this->validated = false;
            WebForm::setInvalidFields($validationData[0]);
        }
    }

    /**
     * @param $field
     */
    protected function regex($field)
    {
        $this->notArray($field);

        if (!preg_match( "@$field[1]@", $this->getFieldValue($field[0]))) {
            $this->errors[$field[0]] = 'is not valid.';
            $this->validated = false;
            WebForm::setInvalidFields($field[0]);
        }
    }

    /**
     * matches american phone number formats
     * i.e. 212-123-1234, (718) 123-1234
     * @param $field
     */
    protected function phone($field)
    {
        $this->notArray($field);

        $phoneRegExt = '^(?:\(\d{3}\)\s)?(?:\d{3}-)?\d{3}-\d{4}$';
        if (!preg_match_all("@$phoneRegExt@", $this->getFieldValue($field))) {
            $this->errors[$field] = 'is not a valid phone number.';
            $this->validated = false;
            WebForm::setInvalidFields($field);
        }
    }


    /**
     * matches a date
     * i.e. 12/31/2015, 2/5/15
     * @param $field
     */
    protected function date($field)
    {
        $this->notArray($field);

        $dateRegEx = '^(?:1[0-2]|0?[1-9])/(?:3[01]|[12][0-9]|0?[1-9])/(?:[0-9]{2})?[0-9]{2}$';
        if (!preg_match_all("@$dateRegEx@", $this->getFieldValue($field))) {
            $this->errors[$field] = 'is not a valid date.';
            $this->validated = false;
            WebForm::setInvalidFields($field);
        }
    }

    /**
     * matches any number
     * i.e. 9, 999, 035, 10.25
     *
     * @param $field
     */
    protected function number($field)
    {
        if (!isset($this->request->{$field})) {
            return;
        }

        $this->notArray($field);

        $numberRegEx = '^[0-9]+$|^[0-9]+\.[0-9]{1,2}$';
        if (!preg_match_all("@$numberRegEx@", $this->getFieldValue($field))) {
            $this->errors[$field] = 'must be a number';
            $this->validated = false;
            WebForm::setInvalidFields($field);
        }
    }

    /**
     * matches an integer
     * i.e 9, 13
     *
     * @param $field
     * @throws ControllerException
     */
    protected function integer($field)
    {
        if (!isset($this->request->{$field})) {
            return;
        }

        $this->notArray($field);

        $numberRegEx = '^[0-9]+$';
        if (!preg_match_all("@$numberRegEx@", $this->getFieldValue($field))) {
            $this->errors[$field] = 'must be a number';
            $this->validated = false;
            WebForm::setInvalidFields($field);
        }
    }


    /**
     * @param array $validationData
     */
    protected function requiredWithout(array $validationData)
    {
        $valid = false;
        $masterField = $validationData[0];
        unset($validationData[0]);

        if ($this->getFieldValue($masterField) == '') {
            foreach ($validationData as $validatedDataAgainst) {
                if (!$this->request->{$validatedDataAgainst} == "") {
                    $valid = true;
                }
            }
            if (!$valid) {
                $this->errors[$masterField] = 'is required when ' . self::keyToName($validationData) . 'empty';
                $this->validated = false;
                WebForm::setInvalidFields($masterField);
            }
        }
    }

    /**
     * @param array $field
     * only calls the validation method when the field is set.
     */
    protected function whenPresent(array $field)
    {
        if ($this->fieldIsset($field[0])) {
            $callable = $field[1];
            unset($field[1]);
            $field = array_values($field);
            $this->caller($callable, $field);
        }
    }

    /**
     * @return bool
     * use 'field' => ['any']
     * the purpose to any is to have a field that is always validated.
     * you can technically make any field validatable, if you want a field inside
     * the validatable array but don't need to validate the field, then call any.
     * May actually be deleted and caught on the validation method but leaving here so it makes more sense.
     */
    protected function any()
    {
        return true;
    }

    /**
     * @param $field
     */
    protected function isBool($field)
    {
        if (!is_bool($this->getFieldValue($field)))
        {
            $this->errors[$field] = 'must be a boolean';
            $this->validated = false;
            WebForm::setInvalidFields($field);
        }
    }

    /**
     * Validates that a field is an array
     *
     * @param $field
     */
    protected function isArray($field)
    {
        if ($this->fieldIsset($field) && !is_array($this->getFieldValue($field))) {
            $this->errors[$field] = 'must be an array';
            $this->validated = false;
            WebForm::setInvalidFields($field);
        }
    }

    /**
     * Just convert form fields from form convention to a more human convention.
     * i.e first-name will become First Name
     * @param $key
     * @return mixed
     */
    private static function keyToName($key)
    {
        $result = str_replace('_', ' ', $key);
        $result = str_replace('-', ' ', $result);
        $result = str_replace('.*.', ' ', $result);

        return ucwords($result);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        foreach ($this->errors as $field => $error) {
            $this->errors[$field] =
                isset($this->customMessage[$field]) ?
                    $this->customMessage[$field] :
                    self::keyToName($field) . ' ' . $this->errors[$field];

        }
        return $this->errors;

    }

    /**
     * @return bool
     */
    public function displayErrors()
    {
        foreach ($this->errors as $field => $error) {
            $this->errors[$field] =
                isset($this->customMessage[$field]) ?
                    $this->customMessage[$field] :
                    self::keyToName($field) . ' ' . $this->errors[$field];

            View::error($this->errors[$field]);
        }

        return true;
    }

    /**
     * @return string
     */
    public function getFirstError()
    {
        if ( $first = key($this->errors) ) {
            $error =
                isset($this->customMessage[$first]) ?
                    $this->customMessage[$first] :
                    self::keyToName($first) . ' ' . $this->errors[$first];
            return $error;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function displayFirstError()
    {
        if ( $first = key($this->errors) ) {
            $error =
                isset($this->customMessage[$first]) ?
                    $this->customMessage[$first] :
                    self::keyToName($first) . ' ' . $this->errors[$first];

            View::error($error);

            return true;
        }

        return false;
    }

    /**
     *
     * Same as getting validated.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->validated;
    }

    /**
     * Fires the appropriate validation method
     *
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws ControllerException
     */
    public function caller($name, $arguments)
    {
        if (method_exists($this, $name)) {
           return call_user_func_array([$this, $name], [$arguments]);
        }

        switch ($name) {
            case 'array':
                return call_user_func_array([$this, 'isArray'], [$arguments]);
        break;
            case 'bool':
            case 'boolean':
            return call_user_func_array([$this, 'isBool'], [$arguments]);
        break;
            case 'numeric':
                return call_user_func_array([$this, 'number'], [$arguments]);
        break;
            default:
                throw new ControllerException("invalid method $name");
        }
    }
}