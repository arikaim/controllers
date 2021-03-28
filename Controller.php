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
use Arikaim\Core\Http\Session;
use Arikaim\Core\Http\Cookie;
use Arikaim\Core\Http\Url;
use Arikaim\Core\Utils\Factory;
use Arikaim\Core\Routes\Route;

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
    protected $extensionName = '';   

    /**
     * Response messages
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Validation error messages
     *
     * @var array|null
     */
    protected $validationErrorMessages = null;

    /**
     * Container
     *
     * @var Container|null
     */
    protected $container = null;

    /**
     * Page name
     *
     * @var string|null
     */
    protected $pageName = null;

    /**
     * Controller params
     *
     * @var array
     */
    protected $params = [];

    /**
     * Data validatin callback
     *
     * @var Closure|null
    */
    protected $dataValidCallback = null;

    /**
     * Data error callback
     *
     * @var Closure|null
    */
    protected $dataErrorCallback = null;

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
     * Get params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params ?? [];
    }

    /**
     * Get param
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Get item from container
     *
     * @param string $id
     * @return mixed
     */
    public function get(string $id)
    {
        if ($this->container->has($id) == false) {
            // try from service container
            return $this->container->get('service')->get($id);
        }

        return $this->container->get($id);
    }

    /**
     * Return tru if container item esist
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
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
    public function getPageName(): ?string
    {
        return $this->pageName;
    }

    /**
     * Get extension name
     *
     * @return string|null
     */
    public function getExtensionName(): ?string
    {
        return ($this->extensionName == 'core') ? null : $this->extensionName;
    }

    /**
     * Set extension name
     *
     * @param string|null $name
     * @return void
     */
    public function setExtensionName(?string $name): void
    {
        $this->extensionName = $name;
    }
    
    /**
     * Get error
     *
     * @param string $errorCode
     * @param array $params
     * @return string|null
     */
    public function getError(string $errorCode, array $params = []): ?string
    {
        $error = $this->getMessage($errorCode);
        
        return (empty($error) == false) ? $error : $this->get('error')->getError($errorCode,$params);
    }

    /**
     * Get url
     *
     * @param ServerRequestInterface $request 
     * @param boolean $relative
     * @return string
     */
    public function getUrl($request, bool $relative = false): string
    {
        $path = $request->getUri()->getPath();

        return ($relative == true ) ? $path : DOMAIN . $path;
    }

    /**
     * Get page url 
     *
     * @param string $path
     * @param boolean $relative
     * @param string|null $language
     * @return string
     */
    public function getPageUrl(string $path = '', bool $relative = false, ?string $language = null): string
    {      
        return Url::getUrl($path,$relative,$language,$this->getDefaultLanguage());
    }

     /**
     * Get page url
     *
     * @param string $routeName
     * @param string $extension
     * @param array $params
     * @param boolean $relative
     * @param string|null $language
     * @return string|false
     */
    public function getRouteUrl($routeName, $extension, $params = [], $language = null, $relative = false)
    {
        $route = $this->container->get('routes')->getRoutes([
            'name'           => $routeName,
            'extension_name' => $extension
        ]);

        if (isset($route[0]) == false) {
            return false;
        }
        $urlPath = Route::getRouteUrl($route[0]['pattern'],$params);
        
        return Url::getUrl($urlPath,$relative,$language);
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
            $callback = function($arguments) use($name) {
                $this->resolveRouteParams($arguments[0]);
                ([$this,$name])($arguments[0],$arguments[1],$arguments[2]);               
                           
                return $this->pageLoad($arguments[0],$arguments[1],$arguments[2],null,null,false);                              
            };

            return $callback($arguments);
        }       
    }

    /**
     * Load messages from html component json file
     *
     * @param string $componentName
     * @param string|null $language
     * @return void
     */
    public function loadMessages(string $componentName, ?string $language = null): void
    {       
        $language = $language ?? $this->getDefaultLanguage();
        $component = $this->get('view')->renderComponent($componentName,[],$language,'json');
        $messages = $component->getProperties();
            
        $this->messages = (empty($messages) == true) ? [] : $messages;           
    }

    /**
     * Load validation error messages
     *
     * @return void
     */
    public function loadValidationErrors(): void
    {
        if (empty($this->validationErrorMessages) == true) {
            $systemValidationErrors = $this->get('errors')->loadValidationErrors();
            $errors = $this->messages['errors']['validation'] ?? [];

            $this->validationErrorMessages = \array_merge($systemValidationErrors,$errors);
        }
    }

    /**
     * Get message
     *
     * @param string $name
     * @return string|null
     */
    public function getMessage(string $name): ?string
    {
        return $this->messages[$name] ?? Arrays::getValue($this->messages,$name,'.');        
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
    public function getUserId(): ?int
    {
        return ($this->has('access') == true) ? $this->get('access')->getId() : null; 
    }

    /**
     * Set callback for validation errors
     *
     * @param Closure $callback
     * @return void
    */
    public function onValidationError(Closure $callback): void
    {
        $this->dataErrorCallback = $callback; 
    }
    
    /**
     * Set callback for validation done
     *
     * @param Closure $callback
     * @return void
     */
    public function onDataValid(Closure $callback): void
    {
        $this->dataValidCallback = $callback;    
    }

    /**
     * Get data validation callback
     *
     * @return Closure|null
     */
    public function getDataValidCallback()
    {
        return $this->dataValidCallback;
    }

    /**
     * Get validation error callback
     *
     * @return Closure|void
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
    public function getRequestParams($request): array
    {
        $params = \explode('/',$request->getAttribute('params'));
        $params = \array_filter($params);
        $vars = $request->getQueryParams();

        return \array_merge($params,$vars);       
    }

    /**
     * Get query param
     *
     * @param ServerRequestInterface $request
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getQueryParam($request, string $name, $default = null)
    {
        $params = $request->getQueryParams();

        return $params[$name] ?? $default;  
    }

    /**
     * Resolve params
     *
     * @param Request $request
     * @param array $paramsKeys
     * @return array
     */
    public function resolveRequestParams($request, array $paramsKeys)
    {
        $params = $this->getRequestParams($request);
        foreach ($paramsKeys as $index => $value) {
            $result[$value] = $params[$index] ?? null;           
        }
        
        return $result;
    }

    /**
     * Require control panel permission
     *
     * @return mixed
     */
    public function requireControlPanelPermission()
    {
        return $this->requireAccess($this->get('access')->getControlPanelPermission(),$this->get('access')->getFullPermissions());
    }
    
    /**
     * Reguire permission check if current user have permission
     *
     * @param string $name
     * @param mixed $type
     * @return bool
     */
    public function requireAccess($name, $type = null): bool
    {       
        if ($this->hasAccess($name,$type) == true) {
            return true;
        }
        $response = $this->get('responseFactory')->createResponse();
        $response = $this->pageSystemError($response);
        Response::emit($response); 
          
        exit();
    }

    /**
     * Return true if user have control panel access
     *
     * @return boolean
     */
    public function hasControlPanelAccess(): bool
    {
        $permissionName = $this->get('access')->getControlPanelPermission();
        $type = $this->get('access')->getFullPermissions();

        return $this->hasAccess($permissionName,$type);
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
        if ($this->has('access') == false) {
            return false;
        }

        return $this->get('access')->hasAccess($name,$type,$this->getUserId());
    }

    /**
     * Return true if page load is with new language code
     *
     * @param array $data
     * @return boolean
     */
    public function isLanguageChange($data): bool
    {
        if (isset($data['language']) == false) {
            return false;
        }

        return (Session::get('language') != $data['language']);
    }

    /**
     * Get page language
     *
     * @param array $data
     * @return string
    */
    public function getPageLanguage($data): string
    {     
        $language = $data['language'] ?? '';
        if (empty($language) == false) {
            return $language;
        }
        
        $language = Cookie::get('language',null);     
        if (empty($language) == false) {
            return $language;
        } 

        $language = Session::get('language',null);

        return $language ?? $this->getDefaultLanguage();           
    }

    /**
     * Get default language
     *
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        return ($this->has('config') == true) ? $this->get('config')->getString('defaultLanguage','en') : 'en';    
    }

    /**
     * Load page
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param CollectionInterface $data   
     * @param string|null     
     * @param boolean $resolveParams 
     * @param string|null $language
     * @return \Psr\Http\Message\ResponseInterface
    */
    public function pageLoad($request, $response, $data, $pageName = null, $language = null, $resolveParams = true)
    {       
        if ($resolveParams == true) {
            $this->resolveRouteParams($request);                        
        }
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
        $data['current_path'] = $request->getAttribute('current_path');
        
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
     * Display page not found
     *    
     * @param ResponseInterface $response
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function pageNotFound($response, array $data = [])
    {          
        $language = $this->getPageLanguage($data);
        $component = $this->get('page')->renderPageNotFound($data,$language);           
        $response->getBody()->write($component->getHtmlCode());

        return $response;        
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
        $language = $this->getPageLanguage($error);
       
        $component = $this->get('page')->renderSystemError($error,$language,$templateName); 
        $response->getBody()->write($component->getHtmlCode());

        return $response;             
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

    /**
     * Log error
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function logError(string $message, array $context = []): bool
    {
        if ($this->has('logger') == true) {
            return $this->get('logger')->error($message,$context);
        }

        return false;
    }

    /**
     * Log message
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function logInfo(string $message, array $context = []): bool
    {
        if ($this->has('logger') == true) {
            return $this->get('logger')->info($message,$context);
        }

        return false;
    }

    /**
     * Resolve route params
     *
     * @param Request $request
     * @return boolean
     */
    protected function resolveRouteParams($request): bool
    {       
        $routeParams = $request->getAttribute('route_params');

        if ($routeParams !== false) {
            // set route params
            $this->pageName = $routeParams['route_page_name'] ?? null;
            if ((\is_null($this->extensionName) == false) && ($this->extensionName != 'core')) {
                $this->extensionName = $routeParams['route_extension_name'] ?? null;
            }
           
            $this->params = (empty($routeParams['route_options']) == false) ? \json_decode($routeParams['route_options'],true) : [];

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
    protected function resolveRouteParam($request, string $paramName): ?string
    {                      
        $routeParams = $request->getAttribute('route_params');  
                        
        return $routeParams[$paramName] ?? null;                    
    }
}
