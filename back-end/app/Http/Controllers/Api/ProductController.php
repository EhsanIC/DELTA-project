<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * List products in a stable order for the frontend catalog.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'products' => Product::query()->orderBy('id')->get(),
        ]);
    }
}
