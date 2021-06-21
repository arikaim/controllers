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

/**
 * UserAccess trait
*/
trait UserAccess 
{     
    /**
     * Reguire permission check if current user have permission
     *
     * @param string $name
     * @param mixed $type
     * @param object|null $response
     * @return void
     */
    public function requireAccess(string $name, $type = null, $response = null)
    {       
        if ($this->hasAccess($name,$type) == true) {
            return true;
        }
        $response = ($response == null) ? $this->get('responseFactory')->createResponse() : $response;
        $response = $this->pageSystemError($response);
        $emitter = new \Slim\ResponseEmitter();
        $emitter->emit($response); 
          
        exit();
    }

    /**
     * Return true if user have control panel access
     *
     * @return boolean
     */
    public function hasControlPanelAccess(): bool
    { 
        return $this->get('access')->hasControlPanelAccess();
    }

    /**
     * Return true if user have access permission
     *
     * @param string $name
     * @param string $type
     * @return boolean
     */
    public function hasAccess($name, $type = null): bool
    {
        return ($this->has('access') == false) ? false : $this->get('access')->hasAccess($name,$type);        
    }

    /**
     * Require control panel permission
     *
     * @param object|null
     * @return mixed
     */
    public function requireControlPanelPermission($response = null)
    {
        return $this->requireAccess(
            $this->get('access')->getControlPanelPermission(),
            $this->get('access')->getFullPermissions(),
            $response
        );
    }
    
    /**
     * Return current logged user
     *
     * @return mixed
     */
    public function user()
    {
        return $this->get('access')->getUser();    
    }

    /**
     * Return current logged user id
     *
     * @return integer|null
     */
    public function getUserId(): ?int
    {
        return $this->get('access')->getId();
    }
}
