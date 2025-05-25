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
use Closure;

/**
 * Orm options trait
*/
trait Options
{   

    /**
     * Before option update callback
     * @var null|Closure
     */
    protected $onBeforeOptionUpdate;

    /**
     * Save options
     *
     * @Api(
     *      description="Save options",    
     *      parameters={
     *          @ApiParameter (name="id",type="integer",description="Options ref Id",required=true),
     *          @ApiParameter (name="options",type="array",description="Options values",required=true)                   
     *      }
     * )
     * 
     * @ApiResponse(
     *      fields={
     *          @ApiParameter (name="uuid",type="string",description="Model uuid")
     *      }
     * )  
     * 
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Arikaim\Core\Validator\Validator $data
     * @return mixed
     */
    public function saveController($request, $response, $data)
    {  
        $data->validate(true);

        $referenceId = $data->get('id');
        $options = $data->get('options',[]);
        $model = Model::create($this->getModelClass(),$this->getExtensionName());
        if ($model == null) {
            $this->error('errors.id');
            return;
        }

        $result = $model->saveOptions($referenceId,$options);
        if ($result === false) {
            $this->error('errors.options.save','Error save options');
            return false;
        }

        $this
            ->message('orm.options.save','Options saved')
            ->field('uuid',$model->uuid);                   
    }

    /**
     * Save single option
     *
     * @Api(
     *      description="Save option",    
     *      parameters={
     *          @ApiParameter (name="id",type="integer",description="Option ref Id",required=true),
     *          @ApiParameter (name="key",type="string",description="Option key name",required=true),
     *          @ApiParameter (name="value",type="mixed",description="Option value",required=true)                      
     *      }
     * )
     * 
     * @ApiResponse(
     *      fields={
     *          @ApiParameter (name="uuid",type="string",description="Model uuid")
     *      }
     * ) 
     * 
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Arikaim\Core\Validator\Validator $data
     * @return mixed
     */
    public function saveOption($request, $response, $data)
    {  
        $data
            ->validate(true);

        $model = Model::create($this->getModelClass(),$this->getExtensionName());
        if ($model == null) {
            $this->error('errors.id');
            return;
        }

        $data = $this->resolveOptionCallback($data,$this->onBeforeOptionUpdate,$model);

        $referenceId = $data->get('id');
        $key = $data->get('key',null);
        $value = $data->get('value',null);

        $result = $model->saveOption($referenceId,$key,$value);
        if ($result === false) {
            $this->error('errors.options.save','Error save option');
            return false;
        }

        $this
            ->message('orm.options.save','Option saved')
            ->field('uuid',$model->uuid);                   
    }

    /**
     * Set before option update callabck
     * @param \Closure $callback
     * @return void
     */
    protected function onBeforeOptionUpdate(Closure $callback): void
    {
        $this->onBeforeOptionUpdate = $callback;
    }

    /**
     * Resolve callback
     *
     * @param mixed $data
     * @param Closure|null $callback
     * @param null|object $model
     * @return mixed
     */
    private function resolveOptionCallback($data, ?Closure $callback, ?object $model = null)
    {
        return (\is_callable($callback) == true) ? $callback($data,$model) : $data;         
    }
}
