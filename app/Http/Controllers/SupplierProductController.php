<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\product;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SupplierProductController extends Controller
{
    
    // جلب الموردين المرتبطين بمنتج معيّن مع بيانات pivot
    public function productSuppliers($productId)
    {
        $product = product::findOrFail($productId);

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
    // ✅ دالة تصدير منتجات المورد إلى Excel
    public function exportSupplierProducts($supplierId)
    {
        $supplier = User::where('role', 'supplier')->findOrFail($supplierId);

        $products = $supplier->suppliedProducts()
            ->withPivot('supplier_price', 'min_quantity', 'active')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // العناوين
        $sheet->fromArray([
            ['ID', 'Title', 'Price', 'Stock', 'Supplier Price', 'Min Quantity', 'Active']
        ]);

        // البيانات
        $rows = [];
        foreach ($products as $product) {
            $rows[] = [
                $product->id,
                $product->titel,
                $product->price,
                $product->stock,
                $product->pivot->supplier_price,
                $product->pivot->min_quantity,
                $product->pivot->active ? 'نشط' : 'موقوف',
            ];
        }
        $sheet->fromArray($rows, null, 'A2');

        // حفظ الملف مؤقتًا وإرساله
        $fileName = 'supplier_'.$supplierId.'_products.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/' . $fileName);
        $writer->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }
    // ✅ دالة تصدير بيانات المنتجات المرتبطة بالمورد فقط (بدون بيانات pivot)
public function exportSupplierProductsData($supplierId)
{
    $supplier = User::where('role', 'supplier')->findOrFail($supplierId);

    $products = $supplier->suppliedProducts()->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // العناوين
    $sheet->fromArray([
        ['ID', 'Title', 'Description', 'Price', 'Stock', 'Brand', 'InCount', 'Counttype', 'inCounttype', 'Discount']
    ]);

    // البيانات
    $rows = [];
    foreach ($products as $product) {
        $rows[] = [
            $product->id,
            $product->titel,
            $product->description,
            $product->price,
            $product->stock,
            $product->brand,
            $product->inCount,
            $product->Counttype,
            $product->inCounttype,
            $product->discount,
        ];
    }

    $sheet->fromArray($rows, null, 'A2');

    // حفظ الملف مؤقتًا وإرساله
    $fileName = 'supplier_'.$supplierId.'_products_data.xlsx';
    $writer = new Xlsx($spreadsheet);
    $tempPath = storage_path('app/' . $fileName);
    $writer->save($tempPath);

    return response()->download($tempPath)->deleteFileAfterSend(true);
}

}

