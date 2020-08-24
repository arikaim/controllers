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
 * Soft Delete trait
*/
trait SoftDelete 
{        
    /**
     * Get soft delete message name
     *
     * @return string
     */
    protected function getSoftDeleteMessage()
    {
        return (isset($this->softDeleteMessage) == true) ? $this->softDeleteMessage : 'delete';
    }

    /**
     * Get restore message name
     *
     * @return string
     */
    protected function getRestoreMessage()
    {
        return (isset($this->restoreMessage) == true) ? $this->restoreMessage : 'restore';
    }

    /**
     * Soft delete model
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function softDeleteController($request, $response, $data)
    {
        $this->onDataValid(function($data) {                  
            $uuid = $data->get('uuid');
            
            $model = Model::create($this->getModelClass(),$this->getExtensionName());
            if (\is_object($model) == false) {
                $this->error('Not valid model class or extension name');
                return;
            }
            $model = $model->findById($uuid);

            $result = (\is_object($model) == false) ? false : $model->softDelete();
              
            $this->setResponse($result,function() use($uuid) {              
                $this
                    ->message($this->getSoftDeleteMessage())
                    ->field('uuid',$uuid);                  
            },'errors.delete');
        });
        $data
            ->addRule('text:min=2|required','uuid')           
            ->validate();       
    }

    /**
     * Restore model
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function restoreController($request, $response, $data)
    {
        $this->onDataValid(function($data) {                  
            $uuid = $data->get('uuid');
            $model = Model::create($this->getModelClass(),$this->getExtensionName());
            if (\is_object($model) == false) {
                $this->error('Not valid model class or extension name');
                return;
            }

            $model = $model->findById($uuid);
            $result = (\is_object($model) == false) ? false : $model->restore();
            
            $this->setResponse($result,function() use($uuid) {              
                $this
                    ->message($this->getRestoreMessage())
                    ->field('uuid',$uuid);                  
            },'errors.restore');
        });
        $data
            ->addRule('text:min=2|required','uuid')           
            ->validate();       
    }
}
