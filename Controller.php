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

use Arikaim\Core\Collection\Arrays;
use Arikaim\Core\Http\Response;
use Arikaim\Core\System\Error\Errors;
use Closure;

/**
 * Base class for all Controllers
*/
class Controller
{
    /**
     * Extension name
     *
     * @var string|null
     */
    protected $extensionName;   

    /**
     * Response messages
     *
     * @var array
     */
    protected $messages;

    /**
     * Validation error messages
     *
     * @var array|null
     */
    protected $validationErrorMessages = null;

    /**
     * Container
     *
     * @var Container
     */
    protected $container;

    /**
     * Page name
     *
     * @var string|null
     */
    protected $page;

    /**
     * Controller params
     *
     * @var array
     */
    protected $params;

    /**
     * Data validatin callback
     *
     * @var Closure
    */
    protected $dataValidCallback;

    /**
     * Data error callback
     *
     * @var Closure
    */
    protected $dataErrorCallback;

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct($container)
    { 
        $this->extensionName = $container->getItem('contoller.extension');
        $this->page = $container->getItem('contoller.page');
        $this->params = $container->getItem('contoller.params',[]);
        $this->messages = [];
        $this->container = $container;
        $this->validationErrorMessages = null;
        
        $this->init();
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        return (empty($this->params) == true) ? [] : $this->params;
    }

    /**
     * Get param
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getParam($key, $default = null)
    {
        return (isset($this->params[$key]) == true) ? $this->params[$key] : $default;
    }

    /**
     * Get item from container
     *
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * Return tru if container item esist
     *
     * @param string $id
     * @return mixed
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * Get container
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get page name
     *
     * @return string|null
     */
    public function getPageName()
    {
        return $this->page;
    }

    /**
     * Get extension name
     *
     * @return string|null
     */
    public function getExtensionName()
    {
        return $this->extensionName;
    }

    /**
     * Set extension name
     *
     * @param string $name
     * @return void
     */
    public function setExtensionName($name)
    {
        $this->extensionName = $name;
    }
    
    /**
     * Add system error
     *
     * @param string $name
     * @return boolean
    */
    public function addError($name)
    {
        $message = $this->getMessage($name);
        $message = (empty($message) == true) ? $name : $message;
        
        if ($this->has('errors') == true) {
            return $this->get('errors')->addError($message);
        }
        
        return false;
    }

    /**
     * Get url
     *
     * @param ServerRequestInterface $request 
     * @param boolean $relative
     * @return string
     */
    public function getUrl($request, $relative = false)
    {
        $path = $request->getUri()->getPath();

        return ($relative == true ) ? $path : DOMAIN . $path;
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
        if (\method_exists($this,$name . 'Page') == true) {
            $callable = [$this,$name . 'Page'];
            $callback = function($arguments) use(&$callable) {
                $this->loadRoute($arguments[0]);

                // get language
                $language = $this->getPageLanguage($arguments[2]);
                $this->setCurrentLanguage($language);
             
                $result = $callable($arguments[0],$arguments[1],$arguments[2]);               
                if ($result === false) {
                    return $this->pageNotFound($arguments[1],$arguments[2],$language);
                }               
                $result = $this->pageLoad($arguments[0],$arguments[1],$arguments[2],$this->getPageName(),false,$language); 
               
                return ($result === false) ? $this->pageNotFound($arguments[1],$arguments[2],$language) : $result;                 
            };

            return $callback($arguments);
        }       
    }

    /**
     * Load messages from html component json file
     *
     * @param string $componentName
     * @param string $language
     * @return void
     */
    public function loadMessages($componentName, $language = null)
    {
        $messages = $this->get('page')->createHtmlComponent($componentName,[],$language)->getProperties();
        $this->messages = (empty($messages) == true) ? [] : $messages;
    }

    /**
     * Load validation error messages
     *
     * @param string $componentName
     * @param string $language
     * @return void
     */
    public function loadValidationErrors($componentName, $language = null)
    {
        $messages = $this->get('page')->createHtmlComponent($componentName,[],$language)->getProperties();
        $this->validationErrorMessages = (empty($messages) == true) ? [] : $messages;
    }

    /**
     * Get message
     *
     * @param string $name
     * @return string
     */
    public function getMessage($name)
    {
        return (isset($this->messages[$name]) == true) ? $this->messages[$name] : Arrays::getValue($this->messages,$name,'.');        
    }

    /**
     * Return current logged user
     *
     * @return mixed
     */
    public function user()
    {
        return ($this->has('access') == true) ? $this->get('access')->getUser() : false;         
    }

    /**
     * Return current logged user id
     *
     * @return integer|null
     */
    public function getUserId()
    {
        return ($this->has('access') == true) ? $this->get('access')->getId() : null; 
    }

    /**
     * Set callback for validation errors
     *
     * @param Closure $callback
     * @return void
    */
    public function onValidationError(Closure $callback)
    {
        $function = function($data) use(&$callback) {
            return $callback->call($this,$data);
        };

        $this->dataErrorCallback = $function;
    }
    
    /**
     * Set callback for validation done
     *
     * @param Closure $callback
     * @return void
     */
    public function onDataValid(Closure $callback)
    {
        $function = function($data) use(&$callback) {
            return $callback->call($this,$data); 
        };       

        $this->dataValidCallback = $function;
    }

    /**
     * Get data validation callback
     *
     * @return Closure
     */
    public function getDataValidCallback()
    {
        return $this->dataValidCallback;
    }

    /**
     * Get validation error callback
     *
     * @return void
     */
    public function getValidationErrorCallback()
    {
        return $this->dataErrorCallback;
    }

    /**
     * Get request params
     *
     * @param Request $request
     * @return array
     */
    public function getRequestParams($request)
    {
        $params = \explode('/', $request->getAttribute('params'));
        $params = \array_filter($params);
        $vars = $request->getQueryParams();

        return \array_merge($params, $vars);       
    }

    /**
     * Get query param
     *
     * @param ServerRequestInterface $request
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getQueryParam($request, $name, $default = null)
    {
        $params = $request->getQueryParams();

        return (isset($params[$name]) == true) ? $params[$name] : $default;  
    }

    /**
     * Resolve params
     *
     * @param Request $request
     * @param array $paramsKeys
     * @return array
     */
    public function resolveRequestParams($request,array $paramsKeys)
    {
        $params = $this->getRequestParams($request);
        foreach ($paramsKeys as $index => $value) {
            $param = (isset($params[$index]) == true) ? $params[$index] : null;
            $result[$value] = $param;
        }
        
        return $result;
    }

    /**
     * Require control panel permission
     *
     * @return void
     */
    public function requireControlPanelPermission()
    {
        if ($this->has('access') == false) {
            return false;
        }

        return $this->requireAccess($this->get('access')->getControlPanelPermission(),$this->get('access')->getFullPermissions());
    }
    
    /**
     * Reguire permission check if current user have permission
     *
     * @param string $name
     * @param mixed $type
     * @return bool
     */
    public function requireAccess($name, $type = null)
    {       
        if ($this->hasAccess($name,$type) == true) {
            return true;
        }
       
        $response = $this->get('errors')->loadSystemError($this->response);
        Response::emit($response); 
          
        exit();
    }

    /**
     * Return true if user have access permission
     *
     * @param string $name
     * @param string $type
     * @return boolean
     */
    public function hasAccess($name, $type = null)
    {
        if ($this->has('access') == false) {
            return false;
        }

        return $this->get('access')->hasAccess($name,$type,$this->getUserId());
    }

    /**
     * Get page language
     *
     * @param array $data
     * @return string
    */
    public function getPageLanguage($data)
    {     
        $language = (isset($data['language']) == true) ? $data['language'] : $this->get('page')->getLanguage();
           
        return (empty($language) == true) ? $this->getDefaultLanguage() : $language;           
    }

    /**
     * Get default language
     *
     * @return string
     */
    public function getDefaultLanguage()
    {
        return ($this->has('options') == true) ? $this->get('options')->get('default.language','en') : 'en';    
    }

    /**
     * Set current language
     *
     * @param string|null $language
     * @return void
     */
    public function setCurrentLanguage($language)
    {
        $language = (empty($language) == true) ? $this->getDefaultLanguage() : $language;

        // add global variable 
        $this->get('view')->addGlobal('current_language',$language);
     
        // set current language
        $this->get('page')->setLanguage($language);
    }   

    /**
     * Load page
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param CollectionInterface $data   
     * @param string|null $name Page name  
     * @param boolean $loadRouteData 
     * @param string|null $language
     * @return Psr\Http\Message\ResponseInterface
    */
    public function pageLoad($request, $response, $data, $pageName = null, $loadRouteData = true, $language = null)
    {       
        if ($loadRouteData == true) {
            $this->loadRoute($request);            
            $pageName = (empty($this->page) == true) ? $pageName : $this->page;                  
        }
        
        if (empty($pageName) == true) {
            $pageName = (isset($data['page_name']) == true) ? $data['page_name'] : $this->resolveRouteParam($request);
        } 

        $data = (\is_object($data) == true) ? $data->toArray() : $data;
        
        if (empty($language) == true) {
            $language = $this->getPageLanguage($data);        
            $this->setCurrentLanguage($language);
        }
        
        if (empty($pageName) == true) {
            return $this->pageNotFound($response,$data,$language);    
        } 
     
        return $this->get('page')->load($response,$pageName,$data,$language);
    }

    /**
     * Redirect
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param CollectionInterface $data   
     * @param string|null $redirectUrl 
     * @return Psr\Http\Message\ResponseInterface
    */
    public function redirect($request, $response, $data, $redirectUrl = null)
    {
        if (empty($redirectUrl) == true) {
            $redirectUrl = (isset($data['redirect_url']) == true) ? $data['redirect_url'] : $this->resolveRouteParam($request,'redirect_url');
        } 

        return $response->withHeader('Location',$redirectUrl);
    }

    /**
     * Display page not found
     *    
     * @param ResponseInterface $response
     * @param array $data
     * @param string|null $language
     * @return Psr\Http\Message\ResponseInterface
     */
    public function pageNotFound($response, $data = [], $language = null)
    {     
        $language = (empty($language) == true) ? $this->getPageLanguage($data) : $language;
        $pageName = Errors::getErrorPageName(Errors::PAGE_NOT_FOUND,$this->getExtensionName());

        return $this->get('page')->load($response,$pageName,$data,$language);       
    }

    /**
     * Display system error page
     *    
     * @param ResponseInterface $response
     * @param array $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function pageSystemError($response, $data = [])
    {     
        $language = $this->getPageLanguage($data);

        return $this->get('errors')->loadSystemError($response,$data,$language,$this->getExtensionName());    
    }

    /**
     * Write XML to reponse body
     *
     * @param ResponseInterface $response
     * @param string $xml
     * @return ResponseInterface
     */
    public function writeXml(ResponseInterface $response, $xml)
    {
        $response->getBody()->write($xml);

        return $response->withHeader('Content-Type','text/xml');
    }

    /**
     * Load route params form route storage
     *
     * @param Request $request
     * @return boolean
     */
    protected function loadRoute($request)
    {
        if ($this->has('routes') == false) {
            return false;
        }
        $pattern = $request->getAttribute('route')->getPattern();
        $route = $this->get('routes')->getRoute('GET',$pattern);
        if ($route != false) {
            // set route params
            $this->page = $route['page_name'];
            $this->setExtensionName($route['extension_name']);
            $this->params = $route['options'];

            return true;
        }

        return false;
    }

    /**
     * Resolve page name
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request 
     * @param string $paramName
     * @return string|null
     */
    protected function resolveRouteParam($request, $paramName = 'page_name')
    {            
        // try from reutes db table
        $route = $request->getAttribute('route');  
        if ((\is_object($route) == true) && ($this->has('routes') == true)) {
            $pattern = $route->getPattern();              
            $routeData = $this->get('routes')->getRoute('GET',$pattern);            
            return (\is_array($routeData) == true) ? $routeData[$paramName] : null;             
        } 
      
        return null;
    }
}
