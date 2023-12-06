<?php

namespace App\Services\Product;

use App\Services\ServiceInterface;
use Illuminate\Http\Request;

interface ProductServiceInterface extends ServiceInterface
{
    public function getRelatedProducts($product, $limit = 4);
    public function getFeaturedProducts();
    public function getProductOnIndex($request);
    public function getProductsByCategory($categoryName, Request $request);
}
