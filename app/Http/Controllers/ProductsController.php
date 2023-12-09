<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Country;


class ProductsController extends Controller
{
    public function index() {
        $products = Product::take(3)->get();
        return response()->json([
            'success' => true,
            'products' =>$products
        ],200);
    }

    public function all() {
        $products = Product::all();
        return response()->json([
            'success' => true,
            'products' =>$products
        ],200);
    }

    public function sukari() {
        $products = Product::where('category',2)->get();
        return response()->json([
            'success' => true,
            'products' =>$products
        ],200);
    }

    public function ajwa() {
        $products = Product::where('category',1)->get();
        return response()->json([
            'success' => true,
            'products' =>$products
        ],200);
    }

    public function sagie() {
        $products = Product::where('category',3)->get();
        return response()->json([
            'success' => true,
            'products' =>$products
        ],200);
    }

    public function mabroom() {
        $products = Product::where('category',4)->get();
        return response()->json([
            'success' => true,
            'products' =>$products
        ],200);
    }

    public function majhool() {
        $products = Product::where('category',5)->get();
        return response()->json([
            'success' => true,
            'products' =>$products
        ],200);
    }

    public function show($slug) {
        $product = Product::where('slug',$slug)->first();
        $sim = Product::where('category',$product->category)->limit(3)->get();
        return response()->json([
            
        'product'=>$product,
        'similar'=>$sim
        ],200);
    }

    public function countries() {
        $product = Country::with('cities')->get();
        return response()->json($product,200);
    }

    public function search(Request $request)
    {
        $products = Product::where('name_en', 'like', "%{$request->term}%")
            ->orWhere('name_ar', 'like', "%{$request->term}%")
            ->get();

            return response()->json([
                'success' => true,
                'products' =>$products
            ],200);
    }


}
