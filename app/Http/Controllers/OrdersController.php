<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Receipt;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $user =    auth('sanctum')->user()->id;


        $order_id = Order::insertGetId([
            'user_id' => $user,
            'name' => auth('sanctum')->user()->name,
            'email' => auth('sanctum')->user()->email,
            'phone' => auth('sanctum')->user()->phone,
            'country' => $request->country,
            'city' => $request->city,
            'paymentMethod' => $request->paymentMethod,
            'address' => $request->address,
            'itemsPrice' =>  $request->itemsPrice,
            'taxPrice' =>  20,
            'shippingPrice' =>  $request->shippingPrice,
            'totalPrice' =>  $request->totalPrice,
            'weight' =>  $request->weight,
         'created_at' => now()
        ]);


  
  $carts = $request->orderItems;

        foreach ($carts as $cart) {
            OrderItem::insert([
                'order_id' => $order_id,
                'product_id' => $cart['id'], // Use array syntax to access 'id'
                'qty' => $cart['qty'], // Use array syntax to access 'qty'
                'price' => $cart['price'], // Use array syntax to access 'price'
                'weight' => $cart['weight'], // Use array syntax to access 'weigh
            ]);
        }

        $order = Order::where('id',$order_id)->first();
        $items = OrderItem::where('order_id' , $order_id)->get();

        return response()->json([
            'success' => true,
            'order' =>$order,
            'orderItems' =>$items
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $order = Order::where('id',$id)->first();
        $items = OrderItem::where('order_id' , $id)->with('product')->get();

        return response()->json([
            'success' => true,
            'order' =>$order,
            'orderItems' =>$items
        ],201);
    }

    public function my_orders()
    {
        $user =    auth('sanctum')->user()->id;
       
        $orders = Order::where('user_id',$user)->get();

        return response()->json($orders,200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function upload(Request $request)
    {
        // $request->validate([
        //     'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        // ]);


        $image = $request->file('image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();

        // Upload the image to the storage
        $image->storeAs('images', $imageName, 'public');

        $rec = new Receipt();
        $rec->order_id = $request->order;
        $rec->image_path = $imageName;
        $rec->save();

        Order::where('id',$request->order)->update(['isPaid'=>5]);

        return response()->json(['message' => 'Image uploaded successfully','data'=>$rec]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
