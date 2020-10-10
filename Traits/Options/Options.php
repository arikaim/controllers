<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Controllers\Traits\Options;

use Arikaim\Core\Db\Model;

/**
 * Orm options trait
*/
trait Options
{   
    /**
     * Save options
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function saveController($request, $response, $data)
    {  
        $this->onDataValid(function($data) {            
            $referenceId = $data->get('id');
            $options = $data->get('options',[]);
            $model = Model::create($this->getModelClass(),$this->getExtensionName());
            if (\is_object($model) == false) {
                $this->error('errors.id');
                return;
            }

            $result = $model->saveOptions($referenceId,$options);
            
            $this->setResponse($result,function() use($model) {
                $this
                    ->message('orm.options.save')
                    ->field('uuid',$model->uuid);                   
            },'errors.options.save');
        });
        $data->validate();
    }

    /**
     * Save single option
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function saveOptionController($request, $response, $data)
    {  
        $this->onDataValid(function($data) {            
            $referenceId = $data->get('id');
            $key = $data->get('key',null);
            $value = $data->get('value',null);

            $model = Model::create($this->getModelClass(),$this->getExtensionName());
            if (\is_object($model) == false) {
                $this->error('errors.id');
                return;
            }

            $result = $model->saveOption($referenceId,$key,$value);
            
            $this->setResponse($result,function() use($model) {
                $this
                    ->message('orm.options.save')
                    ->field('uuid',$model->uuid);                   
            },'errors.options.save');
        });
        $data->validate();
    }
}
