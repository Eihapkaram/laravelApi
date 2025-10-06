<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddToController extends Controller
{
    /**
     * ✅ إضافة منتج للعربة
     */
    public function addfun(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity'   => 'nullable|integer|min:1',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $cart = $user->getcart()->firstOrCreate([]);

            $cartItem = $cart->proCItem()->where('product_id', $request->product_id)->first();

            if ($cartItem) {
                $cartItem->quantity += $request->quantity ?? 1;
                $cartItem->save();
            } else {
                $cart->proCItem()->create([
                    'product_id' => $request->product_id,
                    'quantity'   => $request->quantity ?? 1,
                ]);
            }

            return response()->json([
                'message' => 'Product added to cart successfully',
                'cart'    => $cart->load('proCItem.product'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ عرض محتوى العربة
     */
    public function showCart()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $cart = $user->getcart()->with('proCItem.product')->first();

            if (!$cart || $cart->proCItem->isEmpty()) {
                return response()->json(['message' => 'Cart is empty', 'cart' => []], 200);
            }

            return response()->json(['cart' => $cart], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving cart',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ حذف منتج واحد من العربة
     */
    public function deleteCartItem(Request $request)
    {
        try {
            $request->validate(['product_id' => 'required|exists:products,id']);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $cart = $user->getcart()->first();
            if (!$cart) {
                return response()->json(['message' => 'Cart not found'], 404);
            }

            $item = $cart->proCItem()->where('product_id', $request->product_id)->first();
            if (!$item) {
                return response()->json(['message' => 'Product not found in cart'], 404);
            }

            $item->delete();

            return response()->json([
                'message' => 'Item deleted successfully',
                'cart'    => $cart->load('proCItem.product'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting item',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ حذف جميع المنتجات من العربة
     */
    public function deleteAllCartItems()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $cart = $user->getcart()->first();
            if (!$cart) {
                return response()->json(['message' => 'Cart not found'], 404);
            }

            $cart->proCItem()->delete();

            return response()->json([
                'message' => 'All items deleted successfully',
                'cart'    => [],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting all items',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
