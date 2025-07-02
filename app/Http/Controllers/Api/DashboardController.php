<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Cart;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Support;

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

    public function addToCart(Request $request)
    {
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

    public function showCart()
    {
        $user = auth('sanctum')->user();

        $cart = Cart::where('user_id', $user->id)->with('product')->paginate(10);

        if ($cart->isEmpty()) {

            return response()->json([
                'status' => false,
                'message' => 'Your cart is empty',
            ]);
        }

        return response()->json([
            'status' => true,
            'cart' => $cart,
        ]);
    }

    public function deleteCart($cartId)
    {
        $user = auth('sanctum')->user();
        $cart = Cart::where([
            ['id', $cartId],
            ['user_id', $user->id]
        ])
            ->first();

        if (!$cart) {
            return response()->json([
                'status' => false,
                'message' => 'cart not found',
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'status' => true,
            'message' => 'cart deleted successfully',
        ]);
    }

    public function deleteAllCart()
    {
        $user = auth('sanctum')->user();

        $deleted = Cart::where('user_id', $user->id)->delete(); // delete the cart and return count of deleted rows

        if ($deleted > 0) {
            return response()->json([
                'status' => true,
                'message' => 'All cart items deleted successfully'
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Your cart is already empty',
        ], 403);
    }

    public function createOrder(Request $request)
    {

        $user = auth('sanctum')->user();

        $cart = Cart::where('user_id', $user->id)->get();

        if ($cart->count() > 0) {

            foreach ($cart as $product) {

                Order::create([
                    'user_id' => $user->id,
                    'product_id' => $product->product_id,
                    'quantity' => $product->quantity,
                    'price' => $product->price,
                ]);
            }

            $orders_price = Order::where([
                ['user_id', $user->id],
                ['status', false]
            ])->sum('price');

            return response()->json([
                'status' => true,
                'orders' => $orders_price,
            ]);
        } else {

            return response()->json([
                'message' => 'your cart is empty',
            ]);
        }
    } // end method

    public function showOrders()
    {
        $user = auth('sanctum')->user();

        $data = DB::table('orders')->where('user_id', $user->id)
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->select('orders.quantity', 'orders.price', 'orders.status', 'products.pro_name', 'products.price', 'products.img', 'orders.created_at')
            ->get();

        if (!$data) {
            return response()->json([
                'status' => false,
                'orders' => 'There is no orders',
            ]);
        }
        return response()->json([
            'status' => true,
            'orders' => $data,
        ]);
    } // end method

    public function live_chat(Request $request)
    {

        if ($request->isMethod('post')) {

            // $headers = getallheaders();
            // $token = substr($headers['Authorization'], 7);

            $message = strip_tags($request->message);

            $pusher = new \Pusher\Pusher(
                "5b626e1a0ce2f9d45042",
                "0a19326a9322addb211e",
                "2016147",
                array('cluster' => 'eu')
            );

            $id     = auth('sanctum')->user()->id;
            $name   = auth('sanctum')->user()->name;
            $time   = Carbon::now()->diffForHumans();
            $response = $pusher->trigger('live-chat', 'my-event', ['message' => $message, 'id' => $id, 'name' => $name, 'time' => $time]);

            if ($response == true) {

                $user = auth('sanctum')->user();

                $check = Support::where('sender', $user->id)->first();

                if (isset($check)) {

                    $Support = new Support;
                    $Support->message_no = $check->message_no;
                    $Support->sender = $user->id;
                    $Support->message = $message;
                    $Support->created_at = Carbon::now();
                    $Support->save();
                } else {

                    $rand = rand(100, 100000);
                    $Support = new Support;
                    $Support->message_no = $rand;
                    $Support->sender = $user->id;
                    $Support->message = $message;
                    $Support->created_at = Carbon::now();
                    $Support->save();
                }

                return response()->json([

                    'status'    => true,
                    'message'   => "Your Message Sent Successfully",

                ], 200);
            }


        } else {

            return response()->json([

                'status'    => false,
                'message'   => "This Page Not Found",

            ], 404);
        }
    } // End Method

}
