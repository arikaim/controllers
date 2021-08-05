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

use Arikaim\Core\Controllers\Traits\Base\BaseController;
use Arikaim\Core\Controllers\Traits\Base\ApiResponse;

/**
 * BaseApiController class
*/
class BaseApiController
{    
    use 
        BaseController,
        ApiResponse;     

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct($container = null) 
    {
        $this->container = $container;
        $this->init();
       
        $this->clearResult(); 
    }
    
    /**
     * Init controller, override this method in child classes
     *
     * @return void
    */
    public function init(): void
    {
    }
}