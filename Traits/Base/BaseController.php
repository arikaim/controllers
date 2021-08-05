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

use Arikaim\Core\Http\Url;
use Arikaim\Core\Routes\Route;

/**
 * Controller trait
*/
trait BaseController 
{        
    /**
     * Extension name
     *
     * @var string|null
     */
    protected $extensionName = '';   

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
     * Get page url
     *
     * @param string|null $routeName
     * @param string|null $extension
     * @param array $params
     * @param boolean $relative
     * @param string|null $language
     * @param bool $relative
     * @return string|false
     */
    public function getRouteUrl(
        ?string $routeName,
        ?string $extension, 
        array $params = [], 
        ?string $language = null, 
        bool $relative = false
    )
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
     * Get page url 
     *
     * @param string $path
     * @param boolean $relative
     * @param string|null $language
     * @return string
     */
    public function getPageUrl(string $path = '', bool $relative = false, ?string $language = null): string
    {      
        return Url::getUrl($path,$relative,$language,$language);
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
     * Resolve route params
     *
     * @param Request $request
     * @return boolean
     */
    protected function resolveRouteParams($request): bool
    {       
        $route = $request->getAttribute('route');      
        $routeParams = (empty($route) == false) ? $route->getArguments() : false;

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
     * Get page name
     *
     * @return string|null
     */
    public function getPageName(): ?string
    {
        return $this->pageName;
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
     * Get extension name
     *
     * @return string|null
     */
    public function getExtensionName(): ?string
    {
        return ($this->extensionName == 'core') ? null : $this->extensionName;
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
     * Log message
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function logInfo(string $message, array $context = []): bool
    {
        return ($this->has('logger') == true) ? $this->get('logger')->info($message,$context) : false;     
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
     * Get url
     *
     * @param ServerRequestInterface $request 
     * @param boolean $relative
     * @return string
     */
    public function getUrl($request, bool $relative = false): string
    {
        $path = $request->getUri()->getPath();

        return ($relative == true) ? $path : DOMAIN . $path;
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
        $error = (\method_exists($this,'getMessage') == true) ? $this->getMessage($errorCode) : null;
        
        return (empty($error) == false) ? $error : $this->get('error')->getError($errorCode,$params);
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
        return ($this->has('logger') == true) ? $this->get('logger')->error($message,$context) : false;          
    }
}