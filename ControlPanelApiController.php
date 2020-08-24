<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Controllers;

use Arikaim\Core\Controllers\ControlPanelApiInterface;
use Arikaim\Core\Controllers\ApiController;

/**
 * Base class for all Control Panel Api controllers
*/
class ControlPanelApiController extends ApiController implements ControlPanelApiInterface
{    
    /**
     * Forward calls to $this->response and run Controller function if exist
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (\is_callable([$this->response,$name]) == true) {
            return \call_user_func_array([$this->response,$name], $arguments);     
        }
      
        if (\method_exists($this,$name . 'Controller') == true) {
            $callable = [$this,$name . 'Controller'];
            $callback = function($arguments) use(&$callable) {

                $this->requireControlPanelPermission();
                
                $callable($arguments[0],$arguments[1],$arguments[2]);
                return $this->getResponse();                 
            };
            return $callback($arguments);
        }
    }
}
