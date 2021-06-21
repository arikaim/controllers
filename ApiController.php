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

use Arikaim\Core\Controllers\Traits\Base\BaseController;
use Arikaim\Core\Controllers\Traits\Base\Errors;
use Arikaim\Core\Controllers\Traits\Base\Multilanguage;
use Arikaim\Core\Controllers\Traits\Base\UserAccess;

use Closure;

/**
 * Base class for all Api controllers
*/
class ApiController
{    
    use 
        BaseController,
        Multilanguage,
        UserAccess,
        Errors;

    /**
     * Model class name
     *
     * @var string
     */
    protected $modelClass = null;

    /**
     * Response result
     *
     * @var array
     */
    protected $result;

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
        $this->container = $container;
        $this->init();
       
        // set default validator error callback
        $this->onValidationError(function($errors) {
            $errors = $this->resolveValidationErrors($errors);
            $this->setErrors($errors);
        });

        $this->clearResult(); 
    }
    
    /**
     * Clear result 
     *
     * @return void
     */
    public function clearResult()
    {
        $this->result = [
            'result' => null,
            'status' => 'ok',  
            'code'   => 200, 
            'errors' => []
        ]; 
    }

    /**
     * Dispatch event
     *
     * @param string $eventName
     * @param array $params
     * @return mixed|false
     */
    public function dispatch(string $eventName, $params) 
    {
        return ($this->has('event') == true) ? $this->get('event')->dispatch($eventName,$params) : false;  
    }

    /**
     * Set model class name
     *
     * @param string $class
     * @return void
     */
    public function setModelClass(string $class): void
    {
        $this->modelClass = $class;
    }

    /**
     * Get model class name
     *     
     * @return string|null
     */
    public function getModelClass(): ?string
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
     * Return json 
     * 
     * @param boolean $raw
     * @return string
     */
    public function getResponseJson(bool $raw = false): string
    {
        $this->result = \array_merge($this->result,[
            'errors'          => $this->errors,
            'execution_time'  => (\microtime(true) - (\constant('APP_START_TIME') ?? 0)),
            'status'          => ($this->hasError() == true) ? 'error' : 'ok',
            'code'            => ($this->hasError() == true) ? 400 : 200           
        ]);

        $result = ($raw == true) ? $this->result['result'] : $this->result;
        $code = ($this->prettyFormat == true) ? 
            \json_encode($result,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : \json_encode($result,true);      
        $progressEnd = $this->result['result']['progress_end'] ?? false;

        return (($progressEnd == true) && ($raw == false)) ? ',' . $code . ']' : $code;
    }    

    /**
     * Return response 
     *  
     * @param boolean $raw
     * @return ResponseInterface
     */
    public function getResponse(bool $raw = false)
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
    public function setResultField(string $name, $value): void
    {      
        $this->result['result'][$name] = $value;
    }

    /**
     * Set result filelds
     *
     * @param array $values
     * @param string|null $filedName
     * @return void
     */
    public function setResultFields(array $values, ?string $filedName = null): void
    {      
        foreach ($values as $key => $value) {
            if (empty($filedName) == true) {
                $this->result['result'] = $values;
            } else {
                $this->result['result'][$filedName][$key] = $value;
            }         
        }      
    }

    /**
     * Set result field 
     *
     * @param string $name
     * @param mixed $value
     * @return Self
     */
    public function field(string $name, $value)
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
    public function message(string $name)
    {
        $message = (\method_exists($this,'getMessage') == true) ? $this->getMessage($name) : null;      
        $message = $message ?? $name;
        
        $this->field('message',$message);  
        
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
     * Set response 
     *
     * @param mixed $condition
     * @param array|string|Closure $data
     * @param string|string|Closure $error
     * @return mixed
    */
    public function setResponse(bool $condition, $data, $error)
    {
        $condition = (\is_bool($condition) === true) ? $condition : (bool)$condition;

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
            return (\is_callable($error) == true) ? $error() : $this->error($error);          
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
}
