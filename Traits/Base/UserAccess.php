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

use Arikaim\Core\Access\AccessDeniedException;

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
     * @return void
     * @throws AccessDeniedException
     */
    public function requireAccess(string $name, $type = null): void
    {       
        if ($this->hasAccess($name,$type) == true) {
            return;
        }
        throw new AccessDeniedException('Access Denied');     
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
     * @param mixed $type
     * @return boolean
     */
    public function hasAccess(string $name, $type = null): bool
    {
        return ($this->has('access') == false) ? false : $this->get('access')->hasAccess($name,$type);        
    }

    /**
     * Require control panel permission
     *  
     * @return void
     */
    public function requireControlPanelPermission(): void
    {
        $this->requireAccess(
            $this->get('access')->getControlPanelPermission(),
            $this->get('access')->getFullPermissions()          
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
