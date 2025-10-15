<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\product;
use App\Models\Cart_item;
use Illuminate\Support\Facades\Validator;

class AddToController extends Controller
{
    public function addfun(Request $request)
    {

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|integer|min:1'
        ]);

        $user = auth()->user(); //User modle

        $cart = $user->getcart()->firstOrCreate([]); //Cart modle
        $CartItem = $cart->proCItem()->where('product_id', $request->product_id)->first(); //cartitem items or no
        //لو cartitem فيها العصر صاحب id  عدل quantity
        if ($CartItem) {
            $CartItem->quantity += $request->quantity;
            $CartItem->save();
        } else {
            $cart->proCItem()->create([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }
        return response()->json([
            'message' => 'Product added to cart successfully!',
            'cart' => $cart->load('proCItem.product') //cart
        ]);
    }
    public function CartShow()
    {
        $user = auth()->user(); //User modle
        if (!$user) {
            return response()->json([
                'message' => 'sign in for use cart',
            ], 401);
        }
        $cart = $user->getcart()->firstOrCreate([]);

        // تحمل الـ cart items مع كل منتج
        $cart->load('proCItem.product');

        return response()->json([
            'message' => 'Cart retrieved successfully',
            'cart'    => $cart,
            'user' => $user
        ], 200);
    }
    public function deleteCartItem($id)
    {
        $user = auth()->user(); //User modle
        $cart = $user->getcart()->firstOrCreate([]); //Cart modle
        $CartItem = $cart->proCItem()->find($id);
        $CartItem->delete();
        return response()->json([
            'message' => 'done delete CartItem successfully',
        ], 201);
    }
    public function deleteAllCartItems()
    {
        $user = auth()->user(); //User modle
        $cart = $user->getcart()->firstOrCreate([]); //Cart modle
        $CartItem = $cart->proCItem();
        $CartItem->truncate();
        return response()->json([
            'message' => 'done delete All CartItem successfully',
        ], 201);
    }
}
