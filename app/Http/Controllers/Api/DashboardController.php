<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function home()
    {
        $brands = Brand::all();
        $products = Product::where('avalibale', '1')->get();
        return response()->json([
            'status' => true,
            'message' => 'App Home Page',
            'brand' => $brands,
            'products' => $products,
        ], 200);
    }

    public function ProductsByBrand($id)
    {
        $brand = Brand::where('id', $id)->first();

        $products = Product::where([
            ['id', $brand->id],
            ['avalibale', '1']
        ])->get();

        return response()->json([
            'status' => true,
            'brand' => $brand,
            'products' => $products,
        ], 200);
    }

    public function showProduct($id)
    {
        $product = Product::where('id', $id)->first();
        return response()->json([
            'status' => true,
            'product' => $product,
        ], 200);
    }

    public function filter($filter)
    {
        if ($filter == 'high') {
            $products = Product::orderBy('price', 'desc')->get();
        } else if ($filter == 'low') {

            $products = Product::orderBy('price', 'asc')->get();
        } else if ($filter == 'new') {

            $products = Product::latest()->get();
        } else if ($filter == 'old') {

            $products = Product::get();
        }

        return response()->json([
            'status' => true,
            'product' => $products,
        ], 200);
    }

    public function filterByBrand($brandID, $filter)
    {
        $brand = Brand::where('id', $brandID)->first();

        if ($filter == 'high') {
            $products = Product::where('brand', $brand->id)->orderBy('price', 'desc')->get();
        } else if ($filter == 'low') {

            $products = Product::where('brand', $brand->id)->orderBy('price', 'asc')->get();
        } else if ($filter == 'new') {

            $products = Product::where('brand', $brand->id)->latest()->get();
        } else if ($filter == 'old') {

            $products = Product::where('brand', $brand->id)->get();
        }

        return response()->json([
            'status' => true,
            'brand' => $brand->name,
            'data' => $products,
        ]);
    }

    public function addToFav($product_id)
    {

        $user = auth('sanctum')->user();

        $check = Favorite::where([
            ['product_id', $product_id],
            ['user_id', $user->id],
        ])->first();

        if (isset($check)) {

            return response()->json([
                'status' => false,
                'message' => 'Product already exist',
                'product' => null,
            ], 409);
        }

        $data = new Favorite;
        $data->product_id = $product_id;
        $data->user_id = $user->id;
        $data->save();

        return response()->json([
            'status' => true,
            'message' => 'Product added successfully',
            'product' => $data,
        ], 200);
    }

    public function fav()
    {
        $user = auth('sanctum')->user();

        $products = DB::table('favorites')->where('user_id', '=', $user->id)
            ->join('products', 'favorites.product_id', 'products.id')
            ->select('products.*')->get();

        return response()->json([
            'status' => true,
            'products' => $products,
        ], 200);
    }

    public function removeFav($id)
    {
        $user = auth('sanctum')->user();

        $data = Favorite::where([
            ['product_id', $id],
            ['user_id', $user->id],
        ])->delete();

        if ($data) {
            return response()->json([
                'status' => true,
                'message' => 'Product removed from fav',
            ], 200);
        }

        return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 200);
    }

    public function addToCart(Request $request){
        $data = $request->validate([
            'product_id' => 'required',
            'quantity' => 'required',
            'price' => 'required',
        ]);

        $product = Cart::where('product_id', $data['product_id'])->first();

        if ($product) {
            return response()->json([
                'status' => false,
                'message' => 'You added this product before',
            ], 409);
        }

        $user = auth('sanctum')->user();

        Cart::create([
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'user_id' => $user->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product added to cart successfully',
        ], 200);
    }
}
