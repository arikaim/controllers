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

use Arikaim\Core\Paginator\PaginatorFactory;

/**
 * Api paginator trait
*/
trait ApiPaginator 
{        
    /**
     * Create paginator and set api response fields
     *
     * @param mixed $dataSource
     * @param integer $page
     * @param integer $perPage
     * @return void
     */
    public function paginate($dataSource, int $page = 1, int $perPage = 25): void
    {
        $paginator = PaginatorFactory::create($dataSource,$page,$perPage);
        if ($page > $paginator->getLastPage()) {
            $paginator = PaginatorFactory::create($dataSource,$paginator->getLastPage(),$perPage);
        }
        $items = $paginator->getItems();
        $this
            ->field('paginagtor',$paginator->getPaginatorData())
            ->field('items',(\is_array($items) == false) ? $items->toArray() : $items);
    }
}
