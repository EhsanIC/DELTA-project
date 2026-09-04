<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    /**
     * List customers in a stable order for opportunity selection.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'customers' => Customer::query()->orderBy('name')->get(),
        ]);
    }
}
