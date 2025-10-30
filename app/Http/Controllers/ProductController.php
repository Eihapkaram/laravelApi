<?php

namespace App\Http\Controllers;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Models\product;
use App\Models\categorie;
use Illuminate\Http\Request;
use App\Notifications\NewProduct;
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


        if (!$request) {
            return response()->json(['error' => 'faild edit']);
        }

        $pro = product::findOrFail($id);

        // رفع الصورة الرئيسية الجديدة
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('products', $image, 'public');
            $pro->img = $path ?? $pro->img;
            $pro->save();
        }

        $pro->update([
            'titel' => $request->titel ?? $pro->titel,
            'description' => $request->description ?? $pro->description,
            'votes' => $request->votes ?? $pro->votes,
            'url' => $request->url ?? $pro->url,

            'price' => $request->price ?? $pro->price,
            'stock' => $request->stock ?? $pro->stock,
            'category_id' => $request->category_id ?? $pro->category_id,
            'page_id' => $request->page_id ?? $pro->page_id,
            'brand' => $request->brand ?? $pro->brand,
            'Counttype' => $request->Counttype ?? $pro->Counttype,
            'inCounttype' => $request->inCounttype ?? $pro->inCounttype,
            'discount' =>  $request->discount ?? $pro->discount,
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
                $path1 = $imageUP->storeAs('products', $imageName, 'public');
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
    public function searchByCategory(Request $request)
    {
        $query = $request->input('q'); // اسم الفئة المطلوبة

        $category = categorie::with('product') // نجيب الفئة مع منتجاتها
            ->where('name', 'like', "%{$query}%")
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الفئة المطلوبة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'category' => $category,
        ], 200);
    }


    // ✅ تصدير المنتجات إلى Excel
    public function export()
    {
        $products = Product::with('page')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // العناوين
        $sheet->fromArray([
            ['ID', 'Title', 'Description', 'Votes', 'InCount', 'URL', 'Brand', 'Price', 'Stock', 'Category ID', 'Page ID', 'Counttype', 'inCounttype', 'discount']
        ]);

        // البيانات
        $rows = [];
        foreach ($products as $product) {
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


            ];
        }
        $sheet->fromArray($rows, null, 'A2');

        // حفظ الملف مؤقتًا وإرساله
        $fileName = 'products.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/' . $fileName);
        $writer->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    // ✅ استيراد المنتجات من Excel
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);
        $file = $request->file('file')->getRealPath();

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // تخطي أول صف (العناوين)
        foreach (array_slice($rows, 1) as $row) {
            Product::updateOrCreate(
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
        }

        return response()->json(['message' => 'تم استيراد المنتجات بنجاح']);
    }
}
