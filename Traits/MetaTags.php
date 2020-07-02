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

use Arikaim\Core\Db\Model;

/**
 * MetaTags trait
*/
trait MetaTags 
{        
    /**
     * Get update metatags message name
     *
     * @return string
     */
    protected function getUpdateMetaTagsMessage()
    {
        return ($this->isset($this->updateMetaTagsMessage) == true) ? $this->updateMetaTagsMessage : 'metatags';
    }

    /**
     * Update meta tags
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
    */
    public function updateMetaTagsController($request, $response, $data) 
    {
        $this->onDataValid(function($data) { 
            $uuid = $data->get('uuid');   
            $language = $this->getPageLanguage($data);     
            $model = Model::create($this->getModelClass(),$this->getExtensionName())->findById($uuid);             
            if (is_object($model) == false) {
                $this->error('errors.id');
                return;
            }
        
            $info = $data->slice(['meta_title','meta_description','meta_keywords']);
            $translationModel = $model->saveTranslation($info,$language); 
          
            $this->setResponse(is_object($translationModel),function() use($translationModel) {               
                $this
                    ->message($this->getUpdateMetaTagsMessage())
                    ->field('uuid',$translationModel->uuid);   
            },'errors.metatags');
        });
        $data->validate(); 
    }
}
