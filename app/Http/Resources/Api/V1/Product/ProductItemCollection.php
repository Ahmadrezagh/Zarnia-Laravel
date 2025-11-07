<?php

namespace App\Http\Resources\Api\V1\Product;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductItemCollection extends ResourceCollection
{
    protected $user;

    public function __construct($resource, $user = null)
    {
        parent::__construct($resource);
        $this->user = $user;
    }

    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($item) use ($request) {
                return (new ProductItemResouce($item, $this->user))->toArray($request);
            }),
        ];
    }

    public function with($request)
    {
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

