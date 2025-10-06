<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Cart_item;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AddToController extends Controller
{
    public function addfun(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'quantity'   => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $cart = $user->getcart()->firstOrCreate([]);
            $CartItem = $cart->proCItem()->where('product_id', $request->product_id)->first();

            if ($CartItem) {
                $CartItem->quantity += $request->quantity ?? 1;
                $CartItem->save();
            } else {
                $cart->proCItem()->create([
                    'product_id' => $request->product_id,
                    'quantity'   => $request->quantity ?? 1
                ]);
            }

            return response()->json([
                'message' => 'Product added to cart successfully!',
                'cart'    => $cart->load('proCItem.product')
            ], 200);

        } catch (\Exception $e) {
            Log::error('Add to cart error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function CartShow()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'message' => 'Sign in to use cart',
                ], 401);
            }

            $cart = $user->getcart()->firstOrCreate([]);
            $cart->load('proCItem.product');

            return response()->json([
                'message' => 'Cart retrieved successfully',
                'cart'    => $cart,
                'user'    => $user
            ], 200);

        } catch (\Exception $e) {
            Log::error('Cart show error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                'error'   => $e->getMessage()
            ],
