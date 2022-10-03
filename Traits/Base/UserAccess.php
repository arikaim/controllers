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
     * @param string|integer|null $authId  For current user  - null
     * @return void
     * @throws AccessDeniedException
     */
    public function requireAccess(string $name, $type = null, $authId = null): void
    {       
        if ($this->hasAccess($name,$type,$authId) == true) {
            return;
        }

        throw new AccessDeniedException('Access Denied');     
    }

    /**
     * Check logged user id 
     *
     * @param string|int $userId
     * @return void
     * @throws AccessDeniedException
     */
    public function requireUser($userId): void
    {     
        if ((empty($userId) == true) || ($this->getUserId() != $userId)) {
            throw new AccessDeniedException('Access Denied');   
        }
    }

    /**
     * Check logged user id or contorl panel
     *
     * @param string|int $userId
     * @return void
     * @throws AccessDeniedException
     */
    public function requireUserOrControlPanel($userId): void
    {
        // check for control panel access
        if ($this->hasControlPanelAccess() == true) {
            return;
        }

        // check for user
        $this->requireUser($userId);
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
     * @param string|integer|null $authId  For current user  - null
     * @return boolean
     */
    public function hasAccess(string $name, $type = null, $authId = null): bool
    {
        return ($this->has('access') == false) ? false : $this->get('access')->hasAccess($name,$type,$authId);        
    }

    /**
     * Require control panel permission
     *  
     * @throws AccessDeniedException
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
