<?php

namespace App\Http\Controllers;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Models\product;
use App\Models\categorie;
use Illuminate\Http\Request;
use App\Notifications\NewProduct;
use ZipArchive;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductController extends Controller
{
    public function index()
    {
        $data = product::with('productReviwes', 'images')->get();

        return response()->json([
            'success' => true,
            'message' => 'all products',
            'products' => $data,
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'titel' => 'required',
            'description' => 'required',
            'votes' => 'required',
            'url' => 'required',
            'inCount' => 'required',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
            'price' => 'required',
            'stock' => 'required',
            'category_id' => 'required|min:1',
            'page_id' => 'required|min:1',
            'brand' => 'required',
            'Counttype' => 'required',
            'inCounttype' => 'required',
            'discount' => 'required',
        ]);

        if (!$request) {
            return response()->json(['error' => 'faild in create']);
        }

        // رفع الصورة الرئيسية
        $path = null;
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('products', $image, 'public');
        }

        $imagePath = null;

        $product = product::create([
            'titel' => $request->titel,
            'description' => $request->description,
            'votes' => $request->votes,
            'url' => $request->url,
            'img' => $path,
            'price' => $request->price,
            'stock' => $request->stock,
            'category_id' => $request->category_id,
            'images_url' => $imagePath,
            'page_id' => $request->page_id,
            'brand' => $request->brand,
            'inCount' => $request->inCount,
            'Counttype' => $request->Counttype,
            'inCounttype' => $request->inCounttype,
            'discount' =>  $request->discount,
        ]);
        $user = auth()->user();
        if ($product) {
            // جيب كل المستخدمين اللي رولهم أدمن
            $admins = User::where('role', 'customer')->get();

            // ابعت الإشعار ليهم
            Notification::send($admins, new NewProduct($user, $product));
        }

        // رفع صور إضافية
        if ($request->hasFile('images_url')) {
            foreach ($request->file('images_url') as $image) {
                $imageup = $image->getClientOriginalName();
                $path = $image->storeAs('products', $imageup, 'public');
                $product->images()->create(['path' => $path]);
            }
        }

        $data = product::with('productReviwes', 'images', 'page')->get();

        return response()->json([
            'sucsse' => 'true',
            'message' => 'add item done',
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $product = product::find($id);
        if (is_null($product)) {
            return response()->json([
                'fail' => 'feild',
                'message' => 'product not found',
            ]);
        }

        $categorie = $product->categorie()->get();
        $data = product::with('productReviwes', 'images', 'page', 'categorie')->find($id);

        return response()->json([
            'succss' => 'true',
            'message' => 'product is found',
            'data' => $data,
            'categorie' => $categorie,
        ]);
    }

    public function edit(product $product) {}

    public function update(Request $request, product $product, $id)
    {
        $request->validate([
            'titel' => 'required',
            'description' => 'required',
            'votes' => 'required',
            'url' => 'required',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
            'price' => 'required',
            'stock' => 'required',
            'category_id' => 'required|min:1',
            'page_id' => 'required|min:1',
            'brand' => 'required',
            'Counttype' => 'required',
            'inCounttype' => 'required',
            'discount' => 'required',
        ]);

        if (!$request) {
            return response()->json(['error' => 'faild edit']);
        }

        $pro = product::findOrFail($id);

        // رفع الصورة الرئيسية الجديدة
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('products', $image, 'public');
            $pro->img = $path;
            $pro->save();
        }

        $pro->update([
            'titel' => $request->titel,
            'description' => $request->description,
            'votes' => $request->votes,
            'url' => $request->url,
            'price' => $request->price,
            'stock' => $request->stock,
            'category_id' => $request->category_id,
            'page_id' => $request->page_id,
            'brand' => $request->brand,
            'Counttype' => $request->Counttype,
            'inCounttype' => $request->inCounttype,
            'discount' =>  $request->discount,
        ]);

        // تحديث الصور الإضافية
        $product = product::findOrFail($id);

        if ($request->hasFile('images_url')) {
            foreach ($product->images as $oldImage) {
                Storage::disk('public')->delete($oldImage->path);
                $oldImage->delete();
            }

            foreach ($request->file('images_url') as $imageUP) {
                $imageName = time() . '_' . uniqid() . '.' . $imageUP->getClientOriginalExtension();
                $path = $imageUP->storeAs('products', $imageName, 'public');
                $product->images()->create(['path' => $path]);
            }
        }

        return response()->json([
            'sucsse' => 'true',
            'message' => 'edit item done',
            'imgupdate' => $product->load('images'),
        ]);
    }

    public function destroy($id)
    {
        $product = product::with('images')->find($id);

        if ($product) {
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->path);
                $img->delete();
            }

            $product->delete();

            return response()->json([
                'sucsse' => 'true',
                'data' => $product,
                'message' => 'delete item done',
            ]);
        } else {
            return response()->json([
                'sucsse' => 'false',
                'message' => 'not find item id',
            ]);
        }
    }

    public function search(Request $request)
    {
        $products = QueryBuilder::for(Product::query())
            ->allowedFilters([
                'titel',
                'brand',
                AllowedFilter::exact('categorie.name'),
            ])
            ->allowedIncludes(['page', 'images', 'productReviwes', 'categorie'])
            ->get();

        return response()->json([
            'success' => true,
            'result' => $products,
        ], 200);
    }

    // ✅ تصدير المنتجات إلى Excel
    public function export()
    {
        $products = Product::with('images')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // العناوين
        $sheet->fromArray([
            ['ID', 'Title', 'Description', 'Votes', 'InCount', 'URL', 'Brand', 'Price', 'Stock', 'Category ID', 'Page ID', 'Counttype', 'inCounttype', 'Discount', 'Images']
        ]);

        $rows = [];
        foreach ($products as $product) {
            $imageNames = [];
            foreach ($product->images as $img) {
                $imageNames[] = basename($img->path);
            }

            $rows[] = [
                $product->id,
                $product->titel,
                $product->description,
                $product->votes,
                $product->inCount,
                $product->url,
                $product->brand,
                $product->price,
                $product->stock,
                $product->category_id,
                $product->page_id,
                $product->Counttype,
                $product->inCounttype,
                $product->discount,
                implode(',', $imageNames)
            ];
        }
        $sheet->fromArray($rows, null, 'A2');

        // حفظ ملف Excel مؤقت
        $excelFileName = 'products.xlsx';
        $excelPath = storage_path('app/' . $excelFileName);
        $writer = new Xlsx($spreadsheet);
        $writer->save($excelPath);

        // إنشاء ملف ZIP
        $zipFileName = 'products_with_images.zip';
        $zipPath = storage_path('app/' . $zipFileName);
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            // أضف ملف Excel للـ ZIP
            $zip->addFile($excelPath, $excelFileName);

            // أضف كل الصور للـ ZIP
            foreach ($products as $product) {
                foreach ($product->images as $img) {
                    $fullPath = storage_path('app/public/' . $img->path);
                    if (file_exists($fullPath)) {
                        $zip->addFile($fullPath, 'images/' . basename($img->path));
                    }
                }
            }

            $zip->close();
        }

        // مسح ملف Excel المؤقت بعد إضافته للـ ZIP
        if (file_exists($excelPath)) {
            unlink($excelPath);
        }

        // تنزيل ملف ZIP
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    // ✅ استيراد المنتجات من Excel

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'images_folder' => 'required|string' // المسار النسبي للمجلد اللي فيه الصور
        ]);

        $file = $request->file('file')->getRealPath();
        $imagesFolder = $request->images_folder; // مثال: 'products_import_images'

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach (array_slice($rows, 1) as $row) { // تخطي الصف الأول (العناوين)
            $product = Product::updateOrCreate(
                ['id' => $row[0] ?? null],
                [
                    'titel' => $row[1] ?? '',
                    'description' => $row[2] ?? '',
                    'votes' => $row[3] ?? 0,
                    'inCount' => $row[4] ?? '',
                    'url' => $row[5] ?? '',
                    'brand' => $row[6] ?? '',
                    'price' => $row[7] ?? 0,
                    'stock' => $row[8] ?? 0,
                    'category_id' => $row[9] ?? null,
                    'page_id' => $row[10] ?? null,
                    'Counttype' => $row[11] ?? null,
                    'inCounttype' => $row[12] ?? null,
                    'discount' => $row[13] ?? null,
                ]
            );

            // حذف الصور القديمة للمنتج
            foreach ($product->images as $oldImage) {
                Storage::disk('public')->delete($oldImage->path);
                $oldImage->delete();
            }

            // إضافة الصور من المجلد المحدد
            if (!empty($row[14])) { // عمود الصور في Excel (مفصول بفواصل)
                $imageFiles = explode(',', $row[14]);
                foreach ($imageFiles as $imageName) {
                    $imagePath = $imagesFolder . '/' . trim($imageName);
                    if (Storage::disk('public')->exists($imagePath)) {
                        $product->images()->create(['path' => $imagePath]);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'تم استيراد المنتجات والصور بنجاح'
        ]);
    }
}
