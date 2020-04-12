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

/**
 * Set status trait
*/
trait Status 
{        
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
        $this->requireControlPanelPermission();

        $this->onDataValid(function($data) {
            $status = $data->get('status',1);                
            $uuid = $data->get('uuid');
          
            $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
            $result = (is_object($model) == true) ? $model->setStatus($status) : false; 
        
            $this->setResponse($result,function() use($uuid,$status) {              
                $this
                    ->message('status')
                    ->field('uuid',$uuid)
                    ->field('status',$status);
            },'errors.status');
        });
        $data
            ->addRule('checkList:items=0,1,2,3,4,5,6,7,8,9,10,toggle','status')
            ->validate(); 
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
        $this->requireControlPanelPermission();

        $this->onDataValid(function($data) {       
            $uuid = $data->get('uuid');
            $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
            if (is_object($model) == false) {
                $this->error('errors.default');
                return;
            }

            $result = $model->setDefault($uuid);
        
            $this->setResponse($result,function() use($uuid) {              
                $this
                    ->message('default')
                    ->field('uuid',$uuid);
                  
            },'errors.default');
        });
        $data->validate(); 
    }
}
