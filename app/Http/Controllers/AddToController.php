<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Cart_item;
use App\Models\User;
use App\Notifications\CartItemDeletedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AddToController extends Controller
{
    public function addfun(Request $request)
    {

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $user = auth()->user(); // User modle

        $cart = $user->getcart()->firstOrCreate([]); // Cart modle
        $CartItem = $cart->proCItem()->where('product_id', $request->product_id)->first(); // cartitem items or no
        // لو cartitem فيها العصر صاحب id  عدل quantity
        if ($CartItem) {
            $CartItem->quantity += $request->quantity;
            $CartItem->save();
        } else {
            $cart->proCItem()->create([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        return response()->json([
            'message' => 'Product added to cart successfully!',
            'cart' => $cart->load('proCItem.product'), // cart
        ]);
    }

    public function updateQuantity(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'message' => 'You must be logged in to update cart items',
            ], 401);
        }

        $cart = $user->getcart()->firstOrCreate([]);
        $cartItem = $cart->proCItem()->find($id);

        if (! $cartItem) {
            return response()->json([
                'message' => 'Cart item not found',
            ], 404);
        }

        // تحديث الكمية مباشرة
        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json([
            'message' => 'Quantity updated successfully',
            'cart' => $cart->load('proCItem.product'),
        ], 200);
    }

    public function CartShow()
    {
        $user = auth()->user(); // User modle
        if (! $user) {
            return response()->json([
                'message' => 'sign in for use cart',
            ], 401);
        }
        $cart = $user->getcart()->firstOrCreate([]);

        // تحمل الـ cart items مع كل منتج
        $cart->load('proCItem.product');

        return response()->json([
            'message' => 'Cart retrieved successfully',
            'cart' => $cart,
            'user' => $user,
        ], 200);
    }

    public function deleteCartItem($id)
    {
        $user = auth()->user(); // User model
        $cart = $user->getcart()->firstOrCreate([]); // Cart model
        $cartItem = $cart->proCItem()->find($id);

        if (! $cartItem) {
            return response()->json([
                'message' => 'Cart item not found',
            ], 404);
        }

        $product = $cartItem->product; // 🔹 قبل الحذف

        $cartItem->delete(); // حذف العنصر من الكارت

        // ✅ إرسال إشعار لكل الأدمن
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new CartItemDeletedNotification($user, $product));

        return response()->json([
            'message' => 'Cart item deleted successfully',
        ], 201);
    }

    public function deleteAllCartItems()
    {
        $user = auth()->user(); // User modle
        $cart = $user->getcart()->firstOrCreate([]); // Cart modle
        $CartItem = $cart->proCItem();
        $CartItem->truncate();

        return response()->json([
            'message' => 'done delete All CartItem successfully',
        ], 201);
    }

    public function increaseQuantity(Request $request, $id)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'message' => 'You must be logged in to update cart items',
            ], 401);
        }

        $cart = $user->getcart()->firstOrCreate([]);
        $cartItem = $cart->proCItem()->find($id);
        if (! $cartItem) {
            return response()->json([
                'message' => 'Cart item not found',
            ], 404);
        }

        // ⬆️ زيادة الكمية
        $cartItem->quantity += 1;
        $cartItem->save();

        return response()->json([
            'message' => 'Quantity increased successfully',
            'cart' => $cart->load('proCItem.product'),
        ], 200);
    }

    public function topCustomers()
    {
        $users = User::where('role', 'customer')
            ->withCount([
                'getcart as total_items' => function ($query) {
                    $query->join('cart_items', 'carts.id', '=', 'cart_items.cart_id')
                        ->select(DB::raw('SUM(cart_items.quantity)'));
                },
            ])
            ->orderByDesc('total_items')
            ->take(10)
            ->get();

        return response()->json([
            'message' => 'Top customers',
            'data' => $users,
        ]);
    }

    public function mostAddedProducts()
    {
        $products = Cart_item::select(
            'product_id',
            DB::raw('SUM(quantity) as total_added')
        )
            ->with('product:id,titel,price,img')
            ->groupBy('product_id')
            ->orderByDesc('total_added')
            ->take(10)
            ->get();

        return response()->json([
            'message' => 'Most added products to cart',
            'data' => $products,
        ]);
    }

    public function showAllUsersCarts()
    {
        $user = auth()->user();

        // ✅ السماح للأدمن فقط
        if (! $user || $user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // ✅ جلب عربيات العملاء فقط
        $users = User::where('role', 'customer')
            ->whereHas('getcart') // يرجع اللي عندهم cart فقط
            ->with([
                'getcart.proCItem.product',
            ])
            ->paginate(10); // pagination مهم جداً

        return response()->json([
            'message' => 'All customers carts retrieved successfully',
            'data' => $users,
        ], 200);
    }

    public function decreaseQuantity(Request $request, $id)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'message' => 'You must be logged in to update cart items',
            ], 401);
        }

        $cart = $user->getcart()->firstOrCreate([]);
        $cartItem = $cart->proCItem()->find($id);
        if (! $cartItem) {
            return response()->json([
                'message' => 'Cart item not found',
            ], 404);
        }

        // ⬇️ إنقاص الكمية
        if ($cartItem->quantity > 1) {
            $cartItem->quantity -= 1;
            $cartItem->save();
        } else {
            // ❗ لو عايز تمسحه لما يوصل 1
            $cartItem->delete();

            return response()->json([
                'message' => 'Item removed from cart',
                'cart' => $cart->load('proCItem.product'),
            ], 200);
        }

        return response()->json([
            'message' => 'Quantity decreased successfully',
            'cart' => $cart->load('proCItem.product'),
        ], 200);
    }
}
