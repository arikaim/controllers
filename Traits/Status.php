<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Controllers\Traits;

use Arikaim\Core\Db\Model;
use Closure;

/**
 * Set status trait
*/
trait Status 
{        


    /**
     * Get status changed message
     *
     * @return string
     */
    protected function getStatusChangedMessage(): string
    {
        return $this->statusChangedMessage ?? 'status';
    }

    /**
     * Get set default message 
     *
     * @return string
     */
    protected function getDefaultMessage(): string
    {
        return $this->setDefaultMessage ?? 'default';
    }

    /**
     * Set status changed callback
     *
     * @param Closure $callback
     * @return void
     */
    public function onStatusChanged(Closure $callback): void
    {
        $this->onStatusChanged = $callback;
    }

    /**
     * Set status
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function setStatusController($request, $response, $data)
    {
        $model = Model::create($this->getModelClass(),$this->getExtensionName());
        if ($model == null) {
            $this->error('Not vlaid model.');
            return false;
        }
        
        $this->setModelStatus($data,$model);
    }

    /**
     * Set status
     *
     * @param Validator $data
     * @param object $model
     * @return void|bool
    */
    public function setModelStatus(object $data, object $model)
    {
        $data
            ->addRule('checkList:items=0,1,2,3,4,5,6,7,8,9,10,toggle','status')
            ->validate(true); 

        $status = $data->get('status',1);                
        $uuid = $data->get('uuid');
        
        if (\is_array($uuid) == true) {
            $model = $model->findMultiple($uuid);
            $result = $model->update(['status' => $status]);
        } else {
            $model = $model->findById($uuid);
            $result = ($model == null) ? false : $model->setStatus($status); 
        }
        
        if ($result === false) {
            $this->error('errors.status','Error save status');
            return false;
        }
            
        if (($this->onStatusChanged ?? null) instanceof Closure) {
            ($this->onStatusChanged)($status,$model);  
        }  

        $this
            ->message($this->getStatusChangedMessage())
            ->field('uuid',$uuid)
            ->field('status',$status);
    }

    /**
     * Set default
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function setDefaultController($request, $response, $data)
    {
        $data->validate(true); 
     
        $uuid = $data->get('uuid');
        $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
        if (\is_object($model) == false) {
            $this->error('errors.default');
            return;
        }

        $result = $model->setDefault($uuid);
    
        $this->setResponse($result,function() use($uuid) {              
            $this
                ->message($this->getDefaultMessage())
                ->field('uuid',$uuid);
                
        },'errors.default');
    }

    /**
     * Set multiuser default model
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function setMultiuserDefaultController($request, $response, $data)
    {
        $data->validate(true);     

        $uuid = $data->get('uuid');
        $userId = $data->get('user_id',$this->getUserId());            
        $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
        
        if (\is_object($model) == false) {
            $this->error('errors.class');
            return;
        }

        $result = $model->setDefault($uuid,$userId);
            
        $this->setResponse($result,function() use($uuid) {              
            $this
                ->message($this->getDefaultMessage())
                ->field('uuid',$uuid);                  
        },'errors.default');
    }
}
