<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Controllers\Traits\Base;

use Arikaim\Core\Utils\Text;

/**
 * Errors trait
*/
trait Errors 
{     
    /**
     * Validation error messages
     *
     * @var array|null
     */
    protected $validationErrorMessages = null;

    /**
     * Errors list
     *
     * @var array
     */
    protected $errors = []; 

    /**
     * Set errors 
     *
     * @param array $errors
     * @return void
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * Set error, first find in messages array if not found display name value as error
     *
     * @param string $name
     * @param string|null $default
     * @param array $params
     * @return Self
     */
    public function error(string $name, ?string $default = null, array $params = [])
    {
        $message = (\method_exists($this,'getMessage') == true) ? $this->getMessage($name) : null;
        if (empty($message) == true) {
            // check for system error
            $message = $this->get('errors')->getError($name,$params,$default);           
        }
       
        $message = (empty($message) == true) ? $default ?? $name : $message;        
        $this->setError($message);

        return $this;
    }

    /**
     * Add system error
     *
     * @param string $errorCode
     * @return void
    */
    public function addError(string $errorCode): void
    {
        $message = (\method_exists($this,'getMessage') == true) ? $this->getMessage($errorCode) : null;
        $message = (empty($message) == true) ? $errorCode : $message;
          
        $this->errors[] = $message;      
    }

    /**
     * Get validaiton error message
     *
     * @param string $code
     * @return string|null
     */
    protected function getValidationErrorMessage($code): ?string
    {
        return (isset($this->validationErrorMessages[$code]) == true) ? $this->validationErrorMessages[$code]['message'] : null;
    }

    /**
     * Add errors
     *
     * @param array $errors
     * @return void
     */
    public function addErrors(array $errors): void
    {      
        $this->errors = \array_merge($this->errors,$errors);       
    }

    /**
     * Return errors count
     *
     * @return int
     */
    public function getErrorCount(): int
    {
        return \count($this->errors);
    }

    /**
     * Return true if response have error
     *
     * @return boolean
     */
    public function hasError(): bool 
    {    
        return (\count($this->errors) > 0);         
    }

    /**
     * Set error message
     *
     * @param string $errorMessage
     * @param boolean $condition
     * @return Self
     */
    public function withError(string $errorMessage, bool $condition = true) 
    {
        $this->setError($errorMessage,$condition);

        return $this;
    }

    /**
     * Set error message
     *
     * @param string $errorMessage
     * @param boolean $condition
     * @return void
     */
    public function setError(string $errorMessage, bool $condition = true): void 
    {
        if ($condition !== false) {
            $this->errors[] = $errorMessage;  
        }               
    }

    /**
     * Clear all errors.
     *
     * @return void
    */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Load validation error messages
     *
     * @return void
     */
    public function loadValidationErrors(): void
    {
        if (empty($this->validationErrorMessages) == true) {
            $systemValidationErrors = $this->get('errors')->loadValidationErrors();
            $errors = $this->messages['errors']['validation'] ?? [];

            $this->validationErrorMessages = \array_merge($systemValidationErrors,$errors);
        }
    }

    /**
     * Resolve validation errors
     *
     * @param array $errors
     * @return array
     */
    protected function resolveValidationErrors(array $errors): array
    {
        $result = [];
        $this->loadValidationErrors();

        foreach ($errors as $item) {
            $message = $this->getValidationErrorMessage($item['error_code']);
            $result[] = [
                'field_name' => $item['field_name'],
                'message'    => (empty($message) == false) ? Text::render($message,$item['params']) : $item['error_code']  
            ];
        }

        return $result;
    }
}
