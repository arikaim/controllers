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
 * PageErrors trait
*/
trait PageErrors 
{     
    /**
     * Display page not found error
     *    
     * @param ResponseInterface $response
     * @param array $data
     * @param string|null $templateName
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function pageNotFound($response, array $data = [], ?string $templateName = null)
    {          
        $language = (\method_exists($this,'getPageLanguage') == true) ? $this->getPageLanguage($data) : null;

        $component = $this->get('page')->renderPageNotFound($data,$language,$templateName);        
        $response->getBody()->write($component->getHtmlCode());
      
        return $response->withStatus(404);        
    }

    /**
     * Display system error page
     *    
     * @param ResponseInterface $response
     * @param array $error
     * @param string $templateName
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function pageSystemError($response, $error = [], string $templateName = 'system')
    {     
        $language = (\method_exists($this,'getPageLanguage') == true) ? $this->getPageLanguage($error) : null;
       
        $component = $this->get('page')->renderSystemError($error,$language,$templateName); 
        $response->getBody()->write($component->getHtmlCode());

        return $response->withStatus(400);             
    }
}
