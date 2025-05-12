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
 * Soft Delete trait
*/
trait SoftDelete 
{        
    /**
     * Before soft delete
     *
     * @var Closure|null
     */
    protected $beforeSoftDeleteCallback = null;

    /**
     * Before soft delete callback
     *
     * @param Closure $callback
     * @return void
     */
    protected function onBeforeSoftDelete(Closure $callback): void
    {
        $this->beforeSoftDeleteCallback = $callback;
    }

    /**
     * Get soft delete message name
     *
     * @return string
     */
    protected function getSoftDeleteMessage(): string
    {
        return $this->softDeleteMessage ?? 'delete';
    }

    /**
     * Get restore message name
     *
     * @return string
     */
    protected function getRestoreMessage(): string
    {
        return $this->restoreMessage ?? 'restore';
    }

    /**
     * Soft delete model
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Arikaim\Core\Validator\Validator $data
     * @return mixed
     */
    public function softDelete($request, $response, $data)
    {
        $data
            ->addRule('text:min=1|required','uuid')           
            ->validate(true);      

                      
        $uuid = $data->get('uuid');

        $model = Model::create($this->getModelClass(),$this->getExtensionName());
        if (\is_object($model) == false) {
            $this->error('errors.class','Not valid model class');
            return;
        }

        $model = $model->findById($uuid);
        if ($model == null) {
            $this->error('errors.id','Not valid id');
            return;
        }

        $data = $this->softDeleteResolveCallback($data,$this->beforeSoftDeleteCallback,$model);

        $result = $model->softDelete();
        if ($result === false) {
            $this->error('errors.delete','Error delete');
            return;
        }

        $this
            ->message($this->getSoftDeleteMessage())
            ->field('uuid',$uuid);  
    }

    /**
     * Restore soft deleted model
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Arikaim\Core\Validator\Validator $data
     * @return mixed
     */
    public function restore($request, $response, $data)
    {
        $data
            ->addRule('text:min=2|required','uuid')           
            ->validate(true);     

        $uuid = $data->get('uuid');
        $model = Model::create($this->getModelClass(),$this->getExtensionName());
        if (\is_object($model) == false) {
            $this->error('errors.class','Not valid ');
            return;
        }

        $model = $model->findById($uuid);
        if ($model == null) {
            $this->error('errors.id','Not valid id');
            return;
        }
        
        $result = $model->restore();
        if ($result === false) {
            $this->error('errors.restore','Error restore');
            return;
        }

        $this
            ->message($this->getRestoreMessage())
            ->field('uuid',$uuid);                         
    }

    /**
     * Resolve callback
     * @param mixed $data
     * @param mixed $callback
     * @param mixed $model
     */
    private function softDeleteResolveCallback($data, ?Closure $callback, ?object $model = null)
    {
        return (\is_callable($callback) == true) ? $callback($data,$model) : $data;         
    }
}
