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
use Closure;

/**
 * Relations trait
*/
trait PolymorphicRelations 
{      
    /**
     * Before add relation callback
     *
     * @var Closure|null
     */
    protected $beforeAddRelationCallback = null;

    /**
     * Before delete relation callback
     *
     * @var Closure|null
     */
    protected $beforeDeleteRelationCallback = null;

    /**
     *  Relations model class
     *  @var string|null
     */
    protected $relationsModel = null;

    /**
     *  Relations model extension name
     *  @var string|null
     */
    protected $relationsExtension = null;

    /**
     * Add relation
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function addRelation($request, $response, $data)
    {
        $data->validate(true);
          
        $id = $data->get('id');
        $type = $data->get('type');
        $relationId = $data->get('relation_id');

        $model = Model::create($this->relationsModel,$this->relationsExtension);
        if ($model == null) {
            $this->error('errors.relations.add','Not valid relation model class.');
            return;
        }

        $data = $this->resolveRelationCallback($data,$this->beforeAddRelationCallback,$model);

        //save relation
        $result = $model->saveRelation($id,$type,$relationId);
        if ($result === false || $result === null) {
            $this->error('errors.relations.add','Error save relation.');
            return false;
        }

        $this
            ->message('relations.add','Relation saved')
            ->field('uuid',$result->uuid);
    }

    /**
     * Remove relation
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param Validator $data
     * @return Psr\Http\Message\ResponseInterface
     */
    public function deleteRelation($request, $response, $data)
    {
        $data->validate(true);

        $uuid = $data->get('uuid');
        $id = $data->get('id');
        $type = $data->get('type');
        $relationId = $data->get('relation_id');

        $model = Model::create($this->relationsModel,$this->relationsExtension);
        if ($model == null) {
            $this->error('errors.relations.add','Not valid relation model class.');
            return;
        }
        
        $data = $this->resolveRelationCallback($data,$this->beforeDeleteRelationCallback,$model);

        if (empty($type) == false && empty($relationId) == false) {
            $result = $model->deleteRelations($id,$type,$relationId);
        } else {
            $result = $model->deleteRelation($uuid);
        }
        
        if ($result === false || $result === null) {
            $this->error('errors.relations.delete','Error delete relation.');
            return false;
        }

        $this
            ->message('relations.delete','Relation deleted')
            ->field('uuid',$uuid);        
    }

    /**
     * Resolve relation callback
     *
     * @param mixed $data
     * @param Closure|null $callback
     * @return mixed
     */
    private function resolveRelationCallback($data, ?Closure $callback, ?object $model = null)
    {
        return (\is_callable($callback) == true) ? $callback($data,$model) : $data;         
    }
}
