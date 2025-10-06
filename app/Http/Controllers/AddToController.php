<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Cart_item;
use Illuminate\Support\Facades\Validator;

class AddToController extends Controller
{
    public function addfun(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity'   => 'nullable|integer|min:1'
            ]);

            $user = auth()->user(); // User model

            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated',
                ], 401);
            }

            $cart = $user->getcart()->firstOrCreate([]); // Cart model

            $CartItem = $cart->proCItem()->where('product_id', $request->product_id)->first();

            if ($CartItem) {
                $CartItem->quantity += $request->quantity ?? 1;
                $CartItem->save();
            } else {
                $cart->proCItem()->create([
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity ?? 1
                ]);
            }

            return response()->json([
                'message' => 'Product added to cart successfully!',
                'cart' => $cart->load('proCItem.product')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function CartShow()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'sign in for use cart',
            ], 401);
        }

        $cart = $user->getcart()->firstOrCreate([]);
        $cart->load('proCItem.product');

        return response()->json([
            'message' => 'Cart retrieved successfully',
            'cart'    => $cart,
            'user' => $user
        ], 200);
    }

    public function deleteCartItem($id)
    {
        $user = auth()->user();
        $cart = $user->getcart()->firstOrCreate([]);
        $CartItem = $cart->proCItem()->find($id);

        if (!$CartItem) {
            return response()->json([
                'message' => 'Cart item not found',
            ], 404);
        }

        $CartItem->delete();

        return response()->json([
            'message' => 'CartItem deleted successfully',
        ], 200);
    }

    public function deleteAllCartItems()
    {
        $user = auth()->user();
        $cart = $user->getcart()->firstOrCreate([]);
        $cart->proCItem()->delete();

        return response()->json([
            'message' => 'All cart items deleted successfully',
        ], 200);
    }
}
