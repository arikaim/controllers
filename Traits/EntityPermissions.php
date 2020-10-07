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
     * Get add permission message name
     *
     * @return string
     */
    protected function getAddPermissionMessage()
    {
        return (isset($this->addPermissionMessage) == true) ? $this->addPermissionMessage : 'permission.add';
    }

    /**
     * Get delete permission message name
     *
     * @return string
     */
    protected function getDeletePermissionMessage()
    {
        return (isset($this->deletePermissionMessage) == true) ? $this->deletePermissionMessage : 'permission.delete';
    }

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
            $users = Model::Users();                    
            $entityId = $data->get('entity');
            $user = $data->get('user','');
            $userFound = $users->findUser($user);
        
            if ($userFound === false) {
                $this->error('errors.user.id');
                return;
            }
            $userId = $userFound->id;    
            $permissions = $data->get('permissions','full');

            $model = Model::create($this->getModelClass(),$this->getExtensionName());
            if (\is_object($model) == false) {
                $this->error('errors.id');
                return;
            }

            $permission = $model->addUserPermission($entityId,$userId,$permissions); 
        
            $this->setResponse(\is_object($permission),function() use($permission) {   
                // dispatch event
                $this->dispatch('entity.permission.add',[
                    'permission' => $permission->toArray(),
                    'related'    => $permission->related->toArray(),
                    'entity'     => $permission->entity->toArray()
                ]);

                $this
                    ->message($this->getAddPermissionMessage())
                    ->field('uuid',$permission->uuid);
                    
            },'errors.permission.add');
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
            $permission = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);
            
            if (\is_object($permission) == false) {
                $this->error('errors.id');
                return;
            }
        
            $result = $permission->delete();

            $this->setResponse($result,function() use($uuid,$permission) {   
                // dispatch event  
                $this->dispatch('entity.permission.delete',[
                    'permission' => $permission->toArray(),
                    'related'    => $permission->related->toArray(),
                    'entity'     => $permission->entity->toArray()
                ]);         

                $this
                    ->message($this->getDeletePermissionMessage())
                    ->field('uuid',$uuid);
                  
            },'errors.permission.delete');
        });
        $data->validate(); 
    }
}
