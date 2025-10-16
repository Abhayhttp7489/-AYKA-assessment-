<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;

class ProductController extends Controller
{
    public function index(ProductRepository $repo)
    {
        $products = $repo->paginate(20);
        return view('products.index', compact('products'));
    }
}