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
    public function updateQuantity(Request $request, $id)
{
    $request->validate([
        'quantity' => 'required|integer|min:1'
    ]);

    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => 'You must be logged in to update cart items',
        ], 401);
    }

    $cart = $user->getcart()->firstOrCreate([]);
    $cartItem = $cart->proCItem()->find($id);

    if (!$cartItem) {
        return response()->json([
            'message' => 'Cart item not found',
        ], 404);
    }

    // تحديث الكمية مباشرة
    $cartItem->quantity = $request->quantity;
    $cartItem->save();

    return response()->json([
        'message' => 'Quantity updated successfully',
        'cart'    => $cart->load('proCItem.product')
    ], 200);
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
    public function increaseQuantity(Request $request, $id)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => 'You must be logged in to update cart items',
        ], 401);
    }

     $cart = $user->getcart()->firstOrCreate([]);
    $cartItem = $cart->proCItem()->find($id);

    // ⬆️ زيادة الكمية
    $cartItem->quantity += 1;
    $cartItem->save();

    return response()->json([
        'message' => 'Quantity increased successfully',
        'cart'    => $cart->load('proCItem.product')
    ], 200);
}



public function decreaseQuantity(Request $request, $id)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => 'You must be logged in to update cart items',
        ], 401);
    }

    $cart = $user->getcart()->firstOrCreate([]);
    $cartItem = $cart->proCItem()->find($id);

    // ⬇️ إنقاص الكمية
    if ($cartItem->quantity > 1) {
        $cartItem->quantity -= 1;
        $cartItem->save();
    } else {
        // ❗ لو عايز تمسحه لما يوصل 1
        $cartItem->delete();
        return response()->json([
            'message' => 'Item removed from cart',
            'cart'    => $cart->load('proCItem.product')
        ], 200);
    }

    return response()->json([
        'message' => 'Quantity decreased successfully',
        'cart'    => $cart->load('proCItem.product')
    ], 200);
}

}
