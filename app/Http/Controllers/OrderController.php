<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\OrderInvoices;
use Carbon\Carbon;
use App\Customer;
use App\Product;
use App\Order;
use App\Order_detail;
use App\User;
use Cookie;
use DB;
use PDF;

class OrderController extends Controller
{
    public function addOrder()
    {
        $products = Product::orderBy('created_at', 'DESC')->get();
        return view('orders.add', compact('products'));
    }

    public function getProduct($id)
    {
        $products = Product::findOrFail($id);
        return response()->json($products, 200);
    }

    public function addToCart(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|exists:products,id',
            'qty' => 'required|integer'
        ]);

        $products = Product::findOrFail($request->product_id);
        $getCart = json_decode($request->cookie('cart'), true);

        if ($getCart) {
            if (array_key_exists($request->product_id, $getCart)) {
                $getCart[$request->product_id]['qty'] += $request->qty;
                return response()->json($getCart, 200);
            }
        }

        $getCart[$request->product_id] = [
            'code' => $products->code,
            'name' => $products->name,
            'price' => $products->price,
            'qty' => $products->qty

        ];
        return response()->json($getCart, 200)->cookie('cart', json_encode($getCart), 120);
    }

    public function getCart()
    {
        $cart = json_decode(request()->cookies('cart'), true);
        return response()->json($cart, 200);
    }

    public function removeCart($id)
    {
        $cart = json_decode(request()->cookies('cart'), true);

        unset($cart['id']);

        return response()->json($cart, 200)->cookie('cart', json_encode($cart), 120);

    }

    public function checkout()
    {
        return view('orders.checkout');
    }

    public function storeOrder(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'name' => 'required|string|max:100',
            'address' => 'required',
            'phone' => 'required|numeric'
        ]);

        $cart = json_decode($request->cookie('cart'), true);
        $result = collect($cart)->map(function ($value) {
            return [
                'code' => $value['code'],
                'name' => $value['name'],
                'qty' => $value['qty'],
                'price' => $value['price'],
                'result' => $value['price'] * $value['qty']
            ];
        })->all();

        DB::beginTransaction();
        try {
            $customer = Customer::firstOrCreate([
                'email' => $request->email
            ], [
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone
            ]);

            $order = Order::create([
                'invoice' => $this->generateInvoice(),
                'customer_id' => $customer->id,
                'user_id' => auth()->user()->id,
                'total' => array_sum(array_column($result, 'result'))
            ]);

            foreach ($result as $key => $row) {
                $order->order_detail()->create([
                    'product_id' => $key,
                    'qty' => $row['qty'],
                    'price' => $row['price']
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $order->invoice,
            ], 200)->cookie(Cookie::forget('cart'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function generateInvoice()
    {
        $order = Order::orderBy('created_at', 'DESC');

        if ($order->count() > 0) {
            $order = $order->first();
            $explode = explode('-', $order->invoice);
            return 'INV-' . $explode[1] + 1;
        }
        return 'INV-1';
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }


}
