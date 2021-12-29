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

use Psr\Http\Message\ResponseInterface;

use Arikaim\Core\Http\Session;

use Arikaim\Core\Controllers\Traits\Base\BaseController;
use Arikaim\Core\Controllers\Traits\Base\PageErrors;
use Arikaim\Core\Controllers\Traits\Base\Multilanguage;
use Arikaim\Core\Controllers\Traits\Base\UserAccess;

/**
 * Base class for all Controllers
*/
class Controller
{
    use 
        BaseController,
        Multilanguage,
        UserAccess,
        PageErrors;

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct($container = null)
    {      
        $this->container = $container;
        $this->init();
    }
 
    /**
     * Init controller, override this method in child classes
     *
     * @return void
    */
    public function init()
    {
    }

    /**
     * Call 
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {       
        $name .= 'Page';        
        if (\method_exists($this,$name) == true) {            
            $this->resolveRouteParams($arguments[0]);
            $result = ([$this,$name])($arguments[0],$arguments[1],$arguments[2]);               
            
            if ($result === false) {
                return $this->pageNotFound($arguments[1],$arguments[2]->toArray());  
            }

            return (empty($result) == true) ? $this->pageLoad($arguments[0],$arguments[1],$arguments[2]) : $result;
        }   

        return $this->pageNotFound($arguments[1],$arguments[2]->toArray());    
    }

    /**
     * Load page
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param CollectionInterface $data   
     * @param string|array|null $pageName     
     * @param boolean $resolveParams 
     * @param string|null $language
     * @return \Psr\Http\Message\ResponseInterface
    */
    public function pageLoad($request, $response, $data, $pageName = null, ?string $language = null)
    {       
        $this->resolveRouteParams($request);                        
       
        $data = (\is_object($data) == true) ? $data->toArray() : $data;
        if (empty($pageName) == true || \is_array($pageName) == true) {
            $pageName = $data['page_name'] ?? $this->pageName;
        }

        if (empty($pageName) == true) {
            return $this->pageNotFound($response,$data);    
        } 
        // get current page language
        if (empty($language) == true) {   
            $language = $this->getPageLanguage($data);              
        }

        // set current language
        $this->get('page')->setLanguage($language);
        Session::set('language',$language);  
       
        // current url path     
        $data['current_path'] = $request->getUri()->getPath();
        
        $component = $this->get('page')->render($pageName,$data,$language);
        $response->getBody()->write($component->getHtmlCode());

        return $response;
    }

    /**
     * Set redirect headers
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $url
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function withRedirect($response, string $url)
    {
        return $this
            ->noCacheHeaders($response)      
            ->withHeader('Location',$url)
            ->withStatus(307);
    }

    /**
     * Set no cache in Cache-Control
     *
     * @param @return \Psr\Http\Message\ResponseInterface
     * @return @return \Psr\Http\Message\ResponseInterface
     */
    public function noCacheHeaders($response)
    {
        return $response
            ->withoutHeader('Cache-Control')
            ->withHeader('Cache-Control','no-store, no-cache, must-revalidate, max-age=0')           
            ->withHeader('Pragma','no-cache')              
            ->withHeader('Expires','Sat, 26 Jul 1997 05:00:00 GMT');  
    }

    /**
     * Write XML to reponse body
     *
     * @param ResponseInterface $response
     * @param string $xml
     * @return ResponseInterface
     */
    public function writeXml(ResponseInterface $response, string $xml)
    {
        $response->getBody()->write($xml);

        return $response->withHeader('Content-Type','text/xml');
    }
}
