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

/**
 * Captcha trait
*/
trait Captcha 
{        
    /**
     * Verify captcha
     *   
     * @param \Psr\Http\Message\ServerRequestInterface $request    
     * @param Validator $data
     * @return boolean
    */
    public function verifyCaptcha($request, $data)
    {
        $current = $this->get('options')->get('captcha.current');
        $recaptcha = $this->get('driver')->create($current);

        $result = $recaptcha->verify($data['g-recaptcha-response'],$request->getAttribute('client_ip'));        
        if ($result == false) {
            $this->error('errors.captcha');      
        }   
        
        return $result;
    }
}
