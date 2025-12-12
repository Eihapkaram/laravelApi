<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\product;
use Illuminate\Http\Request;

class SupplierProductController extends Controller
{
    /**
     * ربط منتجات بمورد
     */
    public function attachProducts(Request $request, $supplierId)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        $supplier = User::where('role', 'supplier')->findOrFail($supplierId);

        // sync = استبدال القديم بالجديد
        $supplier->suppliedProducts()->sync($request->product_ids);

        return response()->json([
            'message' => 'Products assigned successfully',
            'supplier_id' => $supplierId,
            'attached_products' => $request->product_ids
        ], 200);
    }
    public function attachProduct(Request $request, $supplierId)
    {
        // تحقق من صحة البيانات
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $supplier = User::where('role', 'supplier')->findOrFail($supplierId);

        // ربط المنتج بالمورد بدون حذف المنتجات السابقة
        $supplier->suppliedProducts()->syncWithoutDetaching([$request->product_id]);

        return response()->json([
            'message' => 'Product assigned successfully',
            'supplier_id' => $supplierId,
            'attached_product' => $request->product_id
        ], 200);
    }


    /**
     * جلب منتجات مورد
     */
    public function supplierProducts($supplierId)
    {
        $supplier = User::where('role', 'supplier')->findOrFail($supplierId);

        return response()->json([
            'supplier' => $supplier->name,
            'products' => $supplier->suppliedProducts()->get()
        ], 200);
    }

    /**
     * جلب الموردين لمنتج معيّن
     */
    public function productSuppliers($productId)
    {
        $product = product::findOrFail($productId);

        return response()->json([
            'product' => $product->name,
            'suppliers' => $product->suppliers()->get()
        ], 200);
    }
}
