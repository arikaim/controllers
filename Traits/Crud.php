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
 * CRUD trait
*/
trait Crud 
{        
    /**
     * Before update
     *
     * @var Closure|null
     */
    protected $beforeUpdateCallback = null;

    /**
     * Before crate
     *
     * @var Closure|null
     */
    protected $beforeCreateCallback = null;

    /**
     * Set before update
     *
     * @param Closure $callback
     * @return void
     */
    protected function onBeforeUpdate(Closure $callback): void
    {
        $this->beforeUpdateCallback = $callback;
    }

    /**
     * Set before create
     *
     * @param Closure $callback
     * @return void
     */
    protected function onBeforeCreate(Closure $callback): void
    {
        $this->beforeCreateCallback = $callback;
    }

    /**
     * Resolve callback
     *
     * @param mixed $data
     * @param Closure|null $callback
     * @return mixed
     */
    private function resolveCallback($data, ?Closure $callback)
    {
        return (\is_callable($callback) == true) ? $callback($data) : $data;         
    }

    /**
     * Get default values
     *
     * @return array
     */
    protected function getDefaultValues(): array
    {
        return $this->defaultValues ?? [];
    } 

    /**
     * Get unique columns
     *
     * @return array
     */
    protected function getUniqueColumns(): array
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
     * Apply default field values
     *
     * @param mixed $data
     * @return mixed
     */
    protected function applyDefaultValues($data) 
    {
        $defaultValues = $this->getDefaultValues();

        foreach ($data as $fieldName => $value) {
            if (empty($value) == true && \array_key_exists($fieldName,$defaultValues) == true) {               
                $data[$fieldName] = $defaultValues[$fieldName];
            }
        }

        return $data;
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

        foreach ($columns as $column) {
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

        $data
            ->addRule('text:min=1|required','uuid')           
            ->validate(true); 
        
        $uuid = $data->get('uuid');
        $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
                    
        $this->setResponse(\is_object($model),function() use($model) {              
            $this
                ->message($this->getReadMessage())
                ->setResultFields($model->toArray());                  
        },'errors.' . $this->getReadMessage());
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

        $data
            ->addRule('text:min=1|required','uuid')           
            ->validate(true);    
 
        $uuid = $data->get('uuid');
        $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
        
        $result = false;
        if (\is_object($model) == true) {
            $result = $this->checkColumn($model,$data,$model->id);
            if ($result == true) {
                $data = $this->applyDefaultValues($data);                  
                $data = $this->resolveCallback($data,$this->beforeUpdateCallback);
                $result = (bool)$model->update($data->toArray());
            }
        }
                    
        $this->setResponse($result,function() use($uuid) {              
            $this
                ->message($this->getUpdateMessage())
                ->field('uuid',$uuid);                  
        },'errors.' . $this->getUpdateMessage());   
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

        $data                  
            ->validate(true);    

        $model = Model::create($this->getModelClass(),$this->getExtensionName());
        
        $createdModel = null;
        if (\is_object($model) == true) {
            $result = $this->checkColumn($model,$data);
            if ($result == true) {
                $data = $this->applyDefaultValues($data);
                $data = $this->resolveCallback($data,$this->beforeCreateCallback);
                $createdModel = $model->create($data->toArray());
            }
        }
                    
        $this->setResponse(\is_object($createdModel),function() use($createdModel) {              
            $this
                ->message($this->getCreateMessage())
                ->field('uuid',$createdModel->uuid);                  
        },'errors.' . $this->getCreateMessage());
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

        $data
            ->addRule('text:min=1|required','uuid')           
            ->validate(true);  

        $uuid = $data->get('uuid');
        $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
        $result = (\is_object($model) == false) ? false : (bool)$model->delete();
            
        $this->setResponse($result,function() use($uuid) {              
            $this
                ->message($this->getDeleteMessage())
                ->field('uuid',$uuid);                  
        },'errors.' . $this->getDeleteMessage());
    }
}
