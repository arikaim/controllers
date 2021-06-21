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

use Arikaim\Core\Http\Cookie;
use Arikaim\Core\Http\Session;
use Arikaim\Core\View\ComponentFactory;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Collection\Arrays;

/**
 * Multilanguage trait
*/
trait Multilanguage 
{     
    /**
     * Response messages
     *
     * @var array
     */
    protected $messages = [];

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
     * Load messages from html component json file
     *
     * @param string $componentName
     * @param string|null $language
     * @return void
     */
    public function loadMessages(string $componentName, ?string $language = null): void
    {       
        $language = $language ?? $this->getDefaultLanguage();
        $component = ComponentFactory::create(
            $componentName,
            $language,
            'json',
            Path::VIEW_PATH,
            Path::EXTENSIONS_PATH,
            $this->get('config')['settings']['primaryTemplate'] ?? 'system'
        );
        $component->resolve([]);
        $messages = $component->getProperties();
            
        $this->messages = (empty($messages) == true) ? [] : $messages;           
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
     * Get default language
     *
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        return $this->get('config')->getString('defaultLanguage','en');
    }
}
