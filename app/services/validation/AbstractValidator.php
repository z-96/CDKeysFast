<?php

namespace Services\Validation;

/**
 * Description of newPHPClass
 *
 * @author Ivan
 */
abstract class AbstractValidator {

    /**
     * Validator
     *
     * @var object
     */
    protected $validator;

    /**
     * Data to be validated
     *
     * @var array
     */
    protected $data = array();

    /**
     * Validation Rules
     *
     * @var array
     */
    protected $rules = array();
    
    /**
     * Error messages
     *
     * @var array
     */
    protected $messages = array();

    /**
     * Validation errors
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Set data to validate
     *
     * @param array $data
     * @return self
     */
    public function with(array $data) {
        $this->data = $data;

        return $this;
    }

    /**
     * Return errors
     *
     * @return array
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Pass the data and the rules to the validator
     *
     * @return boolean
     */
    abstract function passes();
}
