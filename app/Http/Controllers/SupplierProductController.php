<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\product;
use Illuminate\Http\Request;

class SupplierProductController extends Controller
{
    
    // جلب الموردين المرتبطين بمنتج معيّن مع بيانات pivot
    public function productSuppliers($productId)
    {
        $product = Product::findOrFail($productId);

        $suppliers = $product->suppliers()
            ->withPivot('supplier_price', 'min_quantity', 'active')
            ->get();

        return response()->json([
            'product' => $product->name,
            'suppliers' => $suppliers
        ]);
    }
    // جلب منتجات المورد مع بيانات pivot
    public function supplierProducts($supplierId)
    {
        $supplier = User::where('role', 'supplier')->findOrFail($supplierId);

        $products = $supplier->suppliedProducts()
            ->withPivot('supplier_price', 'min_quantity', 'active')
            ->get();

        return response()->json([
            'supplier' => $supplier->name,
            'products' => $products
        ]);
    }

    // ربط منتج بالمورد
    public function attachProduct(Request $request, $supplierId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_price' => 'nullable|numeric',
            'min_quantity' => 'nullable|integer|min:1',
        ]);

        $supplier = User::where('role', 'supplier')->findOrFail($supplierId);

        $supplier->suppliedProducts()->syncWithoutDetaching([
            $request->product_id => [
                'supplier_price' => $request->supplier_price ?? 0,
                'min_quantity' => $request->min_quantity ?? 1,
                'active' => true
            ]
        ]);

        return response()->json([
            'message' => 'Product assigned successfully',
            'supplier_id' => $supplierId,
            'attached_product' => $request->product_id
        ]);
    }

    // حذف منتج من المورد
    public function detachProduct(Request $request, $supplierId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $supplier = User::where('role', 'supplier')->findOrFail($supplierId);
        $supplier->suppliedProducts()->detach($request->product_id);

        return response()->json([
            'message' => 'Product removed successfully',
            'supplier_id' => $supplierId,
            'removed_product' => $request->product_id
        ]);
    }

    // تحديث بيانات pivot (سعر المورد و min_quantity)
    public function updatePivot(Request $request, $supplierId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_price' => 'required|numeric',
            'min_quantity' => 'required|integer|min:1',
            'active' => 'required|boolean'
        ]);

        $supplier = User::where('role', 'supplier')->findOrFail($supplierId);

        $supplier->suppliedProducts()->updateExistingPivot($request->product_id, [
            'supplier_price' => $request->supplier_price,
            'min_quantity' => $request->min_quantity,
            'active' => $request->active
        ]);

        return response()->json([
            'message' => 'Product data updated successfully',
            'supplier_id' => $supplierId,
            'product_id' => $request->product_id
        ]);
    }
}
