<?php

namespace App\Http\Resources\Api\V1\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductListCollection extends ResourceCollection
{
    protected $user;

    public function __construct($resource, $user = null)
    {
        parent::__construct($resource);
        $this->user = $user;
    }

    public function toArray($request)
    {
        // Transform each product using ProductListResource
        return [
            'data' => $this->collection->map(function ($item) use ($request) {
                return (new ProductListResouce($item, $this->user))->toArray($request);
            }),
        ];
    }

    public function with($request)
    {
        // Add pagination meta automatically if the resource is a paginator
        return [
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'last_page'    => $this->resource->lastPage(),
                'per_page'     => $this->resource->perPage(),
                'total'        => $this->resource->total(),
            ],
        ];
    }
}
