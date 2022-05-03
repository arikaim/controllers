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

use Psr\Http\Message\ResponseInterface;

/**
 * ApiResponse trait
*/
trait ApiResponse 
{     
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
     * Return response 
     *  
     * @param boolean $raw
     * @param ResponseInterface|null $response
     * @param bool $progress
     * @return ResponseInterface
     */
    public function getResponse(bool $raw = false, ?ResponseInterface $response = null, bool $progress = false): ResponseInterface
    {
        $json = $this->getResponseJson($raw);
        $json .= ($progress === true) ? ',' : '';

        $response = $response ?? $this->response;
        $response->getBody()->write($json);

        // closure remove
        unset($this->dataErrorCallback);
        unset($this->dataValidCallback);

        return $response
            ->withStatus($this->result['code'])
            ->withHeader('Content-Type','application/json');      
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
     * Set fields
     *
     * @param array $data
     * @param string|null $filedName
     * @return Self
     */
    public function fields(array $data, ?string $filedName = null)
    {
        $this->setResultFields($data,$filedName);
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
     * Return json 
     * 
     * @param boolean $raw
     * @return string
     */
    public function getResponseJson(bool $raw = false): string
    {
        $this->result = \array_merge($this->result,[
            'errors'          => $this->errors,
            'execution_time'  => (\microtime(true) - (\defined('APP_START_TIME') ? APP_START_TIME : $GLOBALS['APP_START_TIME'] ?? 0)),
            'status'          => ($this->hasError() == true) ? 'error' : 'ok',
            'code'            => ($this->hasError() == true) ? 400 : 200           
        ]);

        $result = ($raw == true) ? $this->result['result'] : $this->result;

        return ($this->prettyFormat == true) ? 
            \json_encode($result,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 
            \json_encode($result,true);              
    }    
}
