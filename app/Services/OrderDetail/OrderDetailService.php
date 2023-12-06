<?php

namespace App\Services\OrderDetail;

use App\Repositories\OrderDetail\OrderDetailRepositoryInterface;
use App\Services\BaseService;
use App\Services\Product\ProductServiceInterface;

class OrderDetailService extends BaseService implements OrderDetailServiceInterface
{
    public $repository;

    public function __construct(OrderDetailRepositoryInterface $OrderDetailRepository) {
        $this->repository = $OrderDetailRepository;
    }
}
