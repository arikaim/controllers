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
 * Entity permissions trait
*/
trait EntityPermissions 
{        
    /**
     * Add user permission
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function addUserPermissionController($request, $response, $data)
    {
        $this->onDataValid(function($data) {                       
            $entityId = $data->get('entity');
            $userId = $data->get('user');
            $permissions = $data->get('permissions','full');

            $model = Model::create($this->getModelClass(),$this->getExtensionName());
            if (is_object($model) == false) {
                $this->error('errors.id');
                return;
            }

            $permission = $model->addUserPermission($entityId,$userId,$permissions); 
        
            $this->setResponse(is_object($permission),function() use($permission) {              
                $this
                    ->message('permission-add')
                    ->field('uuid',$permission->uuid);
                    
            },'errors.permission-add');
        });
        $data->validate(); 
    }

    /**
     * Delete permission
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function deletePermissionController($request, $response, $data)
    {
        $this->onDataValid(function($data) {       
            $uuid = $data->get('uuid');
            $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
            
            if (is_object($model) == false) {
                $this->error('errors.id');
                return;
            }
        
            $result = $model->delete();

            $this->setResponse($result,function() use($uuid) {              
                $this
                    ->message('permission-delete')
                    ->field('uuid',$uuid);
                  
            },'errors.permission-delete');
        });
        $data->validate(); 
    }
}
