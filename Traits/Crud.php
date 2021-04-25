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
 * CRUD trait
*/
trait Crud 
{        

    protected function getUniqueColumns()
    {
        return $this->uniqueColumns ?? [];
    } 

    /**
     * Get delete message name
     *
     * @return string
     */
    protected function getDeleteMessage(): string
    {
        return $this->deleteMessage ?? 'delete';
    }

    /**
     * Get update message name
     *
     * @return string
     */
    protected function getUpdateMessage(): string
    {
        return $this->updateMessage ?? 'update';
    }

    /**
     * Get create message name
     *
     * @return string
     */
    protected function getCreateMessage(): string
    {
        return $this->createMessage ?? 'create';
    }

    /**
     * Get read message name
     *
     * @return string
     */
    protected function getReadMessage(): string
    {
        return $this->readMessage ?? 'read';
    }

    /**
     * Check unique columns
     *
     * @param Model|object $model
     * @param Collection $data
     * @param int $excludeId
     * @return bool
     */
    protected function checkColumn($model, $data, $excludeId = null)
    {
        $columns = $this->getUniqueColumns();

        foreach($columns as $column) {
            $value = $data->get($column);
            if (empty($value) == false) {
                $found = $model->where($column,'=',$value)->first();
                if (\is_object($found) == true) {
                    if (empty($excludeId) == true) {
                        return false;
                    } elseif ($found->id != $excludeId) {
                        return false;
                    }                   
                } 
            }           
        }

        return true;
    } 

    /**
     * Read model
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function readController($request, $response, $data)
    {
        $this->requireControlPanelPermission();

        $this->onDataValid(function($data) {                  
            $uuid = $data->get('uuid');
            $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
          
            $result = \is_object($model);
                        
            $this->setResponse($result,function() use($uuid, $model) {              
                $this
                    ->message($this->getReadMessage())
                    ->setResultFields($model->toArray());                  
            },'errors.' . $this->getReadMessage());
        });
        $data
            ->addRule('text:min=2|required','uuid')           
            ->validate();        
    }

    /**
     * Update model
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function updateController($request, $response, $data)
    {
        $this->requireControlPanelPermission();

        $this->onDataValid(function($data) {                  
            $uuid = $data->get('uuid');
            $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
         
            $result = false;
            if (\is_object($model) == true) {
                $result = $this->checkColumn($model,$data,$model->id);
                if ($result == true) {
                    $result = (bool)$model->update($data->toArray());
                }
            }
                        
            $this->setResponse($result,function() use($uuid) {              
                $this
                    ->message($this->getUpdateMessage())
                    ->field('uuid',$uuid);                  
            },'errors.' . $this->getUpdateMessage());
        });
        $data
            ->addRule('text:min=2|required','uuid')           
            ->validate();        
    }

    /**
     * Create model
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function createController($request, $response, $data)
    {
        $this->requireControlPanelPermission();

        $this->onDataValid(function($data) {                             
            $model = Model::create($this->getModelClass(),$this->getExtensionName());
         
            $createdModel = null;
            if (\is_object($model) == true) {
                $result = $this->checkColumn($model,$data);
                if ($result == true) {
                    $createdModel = $model->create($data->toArray());
                }
            }
                        
            $this->setResponse(\is_object($createdModel),function() use($createdModel) {              
                $this
                    ->message($this->getCreateMessage())
                    ->field('uuid',$createdModel->uuid);                  
            },'errors.' . $this->getCreateMessage());
        });
        $data                  
            ->validate();        
    }

    /**
     * Delete model
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function deleteController($request, $response, $data)
    {
        $this->requireControlPanelPermission();

        $this->onDataValid(function($data) {                  
            $uuid = $data->get('uuid');
            $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
            $result = (\is_object($model) == false) ? false : (bool)$model->delete();
               
            $this->setResponse($result,function() use($uuid) {              
                $this
                    ->message($this->getDeleteMessage())
                    ->field('uuid',$uuid);                  
            },'errors.' . $this->getDeleteMessage());
        });
        $data
            ->addRule('text:min=2|required','uuid')           
            ->validate();        
    }
}
