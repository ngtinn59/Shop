<?php

namespace App\Services\Brand;

use App\Repositories\Brand\BrandRepositoryInterface;
use App\Services\BaseService;
use App\Services\Product\ProductServiceInterface;

class BrandService extends BaseService implements BrandServiceInterface
{
    public $repository;

    public function __construct(BrandRepositoryInterface $BrandRepository) {
        $this->repository = $BrandRepository;
    }
}
