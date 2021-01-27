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
 * Update model field trait
*/
trait UpdateModelField 
{        
    /**
     * Get update field message name
     *
     * @return string
     */
    protected function getUpdateFieldMessage(): string
    {
        return $this->updateFieldMessage ?? 'field.update';
    }

    /**
     * Update model field
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function softDeleteController($request, $response, $data)
    {
        $this->onDataValid(function($data) {                  
            $uuid = $data->get('uuid');
            $fieldName =  $data->get('field_name');
            $fieldValue = $data->get('field_value');

            $model = Model::create($this->getModelClass(),$this->getExtensionName());
            if (\is_object($model) == false) {
                $this->error('errors.class');
                return;
            }
            $model = $model->findById($uuid);

            $result = (\is_object($model) == false) ? false : $model->update([
                $fieldName => $fieldValue
            ]);
              
            $this->setResponse($result,function() use($uuid,$fieldName) {              
                $this
                    ->message($this->getUpdateFieldMessage())
                    ->field('uuid',$uuid)
                    ->field('field_name',$fieldName);                  
            },'errors.filed.update');
        });
        $data
            ->addRule('text:min=1|required','uuid') 
            ->addRule('text:min=1|required','field_name')           
            ->validate();       
    }
}
