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
use Arikaim\Core\Http\Response;
use Arikaim\Core\Utils\Text;
use Arikaim\Core\Utils\Utils;

use Arikaim\Core\Controllers\Controller;
use Closure;

/**
 * Base class for all Api controllers
*/
class ApiController extends Controller
{    
    /**
     * Model class name
     *
     * @var string
     */
    protected $modelClass = null;

    /**
     * response result
     *
     * @var array
     */
    protected $result;

    /**
     * Errors list
     *
     * @var array
     */
    protected $errors = []; 

    /**
     * pretty format json 
     *
     * @var bool
     */
    protected $prettyFormat = false;

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct($container = null) 
    {
        parent::__construct($container);
       
        // set default validator error callback
        $this->onValidationError(function($errors) {
            $errors = $this->resolveValidationErrors($errors);
            $this->setErrors($errors);
        });

        $this->result = [
            'result' => null,
            'status' => 'ok',  
            'code'   => 200, 
            'errors' => []
        ];  
    }
   
    /**
     * Set errors 
     *
     * @param array $errors
     * @return void
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
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
     * Run {method name}Controller function if exist
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $name .= 'Controller';
        if (\method_exists($this,$name) == true) {
            $callback = function($arguments) use($name) {
                $this->resolveRouteParams($arguments[0]);
                ([$this,$name])($arguments[0],$arguments[1],$arguments[2]);

                return $this->getResponse();                 
            };
            
            return $callback($arguments);
        }
    }

    /**
     * Set error message
     *
     * @param string $errorMessage
     * @param boolean $condition
     * @return void
     */
    public function setError($errorMessage, $condition = true) 
    {
        if ($condition !== false) {
            \array_push($this->errors,$errorMessage);  
        }               
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
     * Clear all errors.
     *
     * @return void
    */
    public function clearErrors()
    {
        $this->errors = [];
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

    /**
     * Return true if response have error
     *
     * @return boolean
     */
    public function hasError() 
    {    
        return (count($this->errors) > 0);         
    }

    /**
     * Return json 
     * 
     * @param boolean $raw
     * @return string
     */
    public function getResponseJson($raw = false)
    {
        $this->result = \array_merge($this->result,[
            'errors'          => $this->errors,
            'executeion_time' => Utils::getExecutionTime(),
            'status'          => ($this->hasError() == true) ? 'error' : 'ok',
            'code'            => ($this->hasError() == true) ? 400 : 200           
        ]);

        $result = ($raw == true) ? $this->result['result'] : $this->result;
    
        return ($this->prettyFormat == true) ? Utils::jsonEncode($result) : \json_encode($result,true);      
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
        $json = $this->getResponseJson($raw);

        $response = $this->get('responseFactory')->createResponse();
        $response->getBody()->write($json);

        return $response
            ->withStatus($this->result['code'])
            ->withHeader('Content-Type','application/json');      
    }

    /**
     * Set field to result array 
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setResultField($name, $value)
    {      
        $this->result['result'][$name] = $value;
    }

    /**
     * Set result field 
     *
     * @param string $name
     * @param mixed $value
     * @return Self
     */
    public function field($name, $value)
    {
        $this->setResultField($name,$value);

        return $this;
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
        $message = $message ?? $name;
        
        $this->field('message',$message);  
        
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
        $this->setError($message);

        return $this;
    }

    /**
     * Set json pretty format to true
     *
     * @return Self
     */
    public function useJsonPrettyformat()
    {
        $this->prettyFormat = true;

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
        $this->errors = \array_merge($this->errors,$errors);       
    }

     /**
     * Set error message
     *
     * @param string $errorMessage
     * @param boolean $condition
     * @return Self
     */
    public function withError($errorMessage, $condition = true) 
    {
        $this->setError($errorMessage,$condition);

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
        if ($condition !== false) {
            if (\is_callable($data) == true) {
                return $data();
            } 
            if (\is_array($data) == true) {
                return $this->setResult($data);
            }
            if (\is_string($data) == true) {
                return $this->message($data);
            }
        } else {
            return (\is_callable($error) == true) ? $error() : $this->setError($error);          
        }
    }

    /**
     * Set response result
     *
     * @param mixed $data
     * @return Self
     */
    public function setResult($data) 
    {
        $this->result['result'] = $data;   

        return $this;
    }

    /**
     * Return errors count
     *
     * @return int
     */
    public function getErrorCount()
    {
        return count($this->errors);
    }
}
