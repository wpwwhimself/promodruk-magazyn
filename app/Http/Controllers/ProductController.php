<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getAttributes(int $id = null)
    {
        $data = ($id)
            ? Attribute::with("variants")->findOrFail($id)
            : Attribute::with("variants")->get();
        return response()->json($data);
    }

    public function getProducts(string $id = null)
    {
        $data = ($id)
            ? Product::with("attributes.variants")->findOrFail($id)
            : Product::with("attributes.variants")->get();
        return response()->json($data);
    }
}
