<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Controllers;

use Psr\Http\Message\ResponseInterface;
use Arikaim\Core\Http\ApiResponse;
use Arikaim\Core\Http\Response;
use Arikaim\Core\Utils\Text;

use Arikaim\Core\Controllers\Controller;
use Closure;

/**
 * Base class for all Api controllers
*/
class ApiController extends Controller
{    
    /**
     * Api response
     *
     * @var ApiResponse
     */
    protected $response;

    /**
     * Model class name
     *
     * @var string
     */
    protected $modelClass;

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct($container) 
    {
        parent::__construct($container);

        $this->response = new ApiResponse(Response::create());  
        // set default validator error callback
        $this->onValidationError(function($errors) {
            $errors = $this->resolveValidationErrors($errors);
            $this->setErrors($errors);
        });

        $this->modelClass = null;
    }
   
    /**
     * Dispatch event
     *
     * @param string $eventName
     * @param array $params
     * @return mixed|false
     */
    public function dispatch($eventName, $params) 
    {
        return ($this->has('event') == true) ? $this->get('event')->dispatch($eventName,$params) : false;  
    }

    /**
     * Set model class name
     *
     * @param string $class
     * @return void
     */
    public function setModelClass($class)
    {
        $this->modelClass = $class;
    }

    /**
     * Get model class name
     *     
     * @return string
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }

    /**
     * Add message to response, first find in messages array if not found display name value as message 
     *
     * @param string $name  
     * @return ApiController
     */
    public function message($name)
    {
        $message = $this->getMessage($name);
        $message = (empty($message) == true) ? $name : $message;
        
        $this->response->message($message);      
        
        return $this;
    }

    /**
     * Set error, first find in messages array if not found display name value as error
     *
     * @param string $name
     * @return ApiController
     */
    public function error($name)
    {
        $message = $this->getMessage($name);
        if (empty($message) == true) {
            // check for system error
            $message = $this->get('errors')->get($name,null);
        }
        $message = (empty($message) == true) ? $name : $message;        
        $this->response->setError($message);

        return $this;
    }

    /**
     * Add errors
     *
     * @param array $errors
     * @return void
     */
    public function addErrors(array $errors)
    {
        $this->response->addErrors($errors);
    }

    /**
     * Set response field
     *
     * @param string $name
     * @param mixed $value
     * @return ApiController
     */
    public function field($name, $value)
    {
        $this->response->field($name,$value);
        
        return $this;
    }

    /**
     * Set response 
     *
     * @param boolean $condition
     * @param array|Closure $data
     * @param string|Closure $error
     * @return mixed
    */
    public function setResponse($condition, $data, $error)
    {
        if (\is_string($error) == true) {
            $message = $this->getMessage($error);
            $error = (empty($message) == true) ? $error : $message;
        }
        if (\is_string($data) == true) {
            $message = $this->getMessage($data);
            $data = (empty($message) == true) ? $data : $message;
        }
        
        return $this->response->setResponse($condition,$data,$error);
    }

    /**
     * Forward calls to $this->response and run Controller function if exist
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (\is_callable([$this->response,$name]) == true) {
            return \call_user_func_array([$this->response,$name], $arguments);     
        }
      
        if (\method_exists($this,$name . 'Controller') == true) {
            $callable = [$this,$name . 'Controller'];
            $callback = function($arguments) use(&$callable) {
                $callable($arguments[0],$arguments[1],$arguments[2]);
                return $this->getResponse();                 
            };
            
            return $callback($arguments);
        }
    }

    /**
     * Return response 
     *  
     * @param boolean $raw
     * 
     * @return ResponseInterface
     */
    public function getResponse($raw = false)
    {
        return $this->response->getResponse($raw);
    }

    /**
     * Reguire permission check if current user have permission
     *
     * @param string $name
     * @param mixed $type
     * @return bool
     */
    public function requireAccess($name, $type = null)
    {       
        if ($this->hasAccess($name,$type) == true) {
            return true;
        }
        
        $this->setError($this->get('errors')->getError('AUTH_FAILED'));                        
        Response::emit($this->getResponse()); 

        exit();       
    }

    /**
     * Resolve validation errors
     *
     * @param array $errors
     * @return array
     */
    protected function resolveValidationErrors($errors)
    {
        $result = [];
        if (\is_array($this->validationErrorMessages) == false) {
            $this->validationErrorMessages = $this->get('errors')->loadValidationErrors();
        }

        foreach ($errors as $item) {
            $message = $this->getValidationErrorMessage($item['error_code']);
            $result[] = [
                'filed_name' => $item['field_name'],
                'message'    => (empty($message) == false) ? Text::render($message,$item['params']) : $item['error_code']  
            ];
        }

        return $result;
    }

    /**
     * Get validaiton error message
     *
     * @param string $code
     * @return string|null
     */
    protected function getValidationErrorMessage($code)
    {
        return (isset($this->validationErrorMessages[$code]) == true) ? $this->validationErrorMessages[$code]['message'] : null;
    }
}
