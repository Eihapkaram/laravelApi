<?php

namespace App\Http\Controllers;

use App\Models\categorie;
use App\Models\product;
use App\Models\User;
use App\Notifications\NewProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

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

    public function index2()
    {
        $data = product::paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'all products',
            'products' => $data,
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'titel' => 'required|string|max:255',
            'description' => 'required|string',
            'votes' => 'required|numeric',
            'url' => 'required|string',
            'inCount' => 'required',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // تحديد الحجم الأقصى 2 ميجا لحماية السيرفر
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'category_id' => 'required|integer|min:1',
            'page_id' => 'required|integer|min:1',
            'brand' => 'required|string',
            'Counttype' => 'required',
            'inCounttype' => 'required',
            'discount' => 'required|numeric',
        ]);

        if (! $request) {
            return response()->json(['error' => 'faild in create']);
        }

        // رفع الصورة الرئيسية - تأمين الاسم عبر توليد اسم فريد يمنع ثغرات الـ PHP Shell
        $path = null;
        if ($request->hasFile('img')) {
            $imageExtension = $request->file('img')->getClientOriginalExtension();
            $imageName = time() . '_' . uniqid() . '.' . $imageExtension;
            $path = $request->file('img')->storeAs('products', $imageName, 'public');
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
            'discount' => $request->discount,
        ]);
        
        $user = auth()->user();
        if ($product) {
            // جيب كل المستخدمين اللي رولهم أدمن
            $admins = User::where('role', 'customer')->get();

            // ابعت الإشعار ليهم
            Notification::send($admins, new NewProduct($user, $product));
        }

        // رفع صور إضافية - تأمين الأسماء
        if ($request->hasFile('images_url')) {
            foreach ($request->file('images_url') as $image) {
                $imageExtension = $image->getClientOriginalExtension();
                $imageup = time() . '_' . uniqid() . '.' . $imageExtension;
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
        // تأمين الـ ID بالتأكد من أنه قيمة رقمية لصد أي محاولات Inject
        $id = (int)$id;
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
    if (! $request) {
        return response()->json(['error' => 'faild edit']);
    }

    // إضافة الـ Validation لحماية البيانات المدخلة في التحديث
    $request->validate([
        'titel' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'votes' => 'nullable|numeric',
        'url' => 'nullable|string',
        'price' => 'nullable|numeric',
        'stock' => 'nullable|integer',
        'category_id' => 'nullable|integer|min:1',
        'page_id' => 'nullable|integer|min:1',
        'brand' => 'nullable|string',
        'discount' => 'nullable|numeric',
    ]);

    $id = (int)$id;
    $pro = product::findOrFail($id);

    // رفع الصورة الرئيسية الجديدة بأمان (لو مش موجودة في الـ Request، هيفضل محتفظ بالقديمة تلقائياً)
    if ($request->hasFile('img')) {
        // حذف الصورة القديمة من السيرفر لتوفير المساحة والأمان
        if ($pro->img) {
            Storage::disk('public')->delete($pro->img);
        }
        $imageExtension = $request->file('img')->getClientOriginalExtension();
        $imageName = time() . '_' . uniqid() . '.' . $imageExtension;
        $path = $request->file('img')->storeAs('products', $imageName, 'public');
        $pro->img = $path;
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
        'discount' => $request->discount ?? $pro->discount,
    ]);

    // تحديث الصور الإضافية (لو مرفعش صور جديدة، هيفضل محتفظ بالقديمة كاملة بدون أي حذف)
    if ($request->hasFile('images_url')) {
        // الحذف مبيتمش إلا لو الشرط دا تحقق ودخلنا هنا فعلياً
        foreach ($pro->images as $oldImage) {
            Storage::disk('public')->delete($oldImage->path);
            $oldImage->delete();
        }

        foreach ($request->file('images_url') as $imageUP) {
            $imageName = time().'_'.uniqid().'.'.$imageUP->getClientOriginalExtension();
            $path1 = $imageUP->storeAs('products', $imageName, 'public');
            $pro->images()->create(['path' => $path1]);
        }
    }

    return response()->json([
        'sucsse' => 'true',
        'message' => 'edit item done',
        'imgupdate' => $pro->load('images'),
    ]);
}
    public function destroy($id)
    {
        $id = (int)$id;
        $product = product::with('images')->find($id);

        if ($product) {
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->path);
                $img->delete();
            }

            // حذف الصورة الرئيسية أيضاً عند حذف المنتج لحماية مساحة السيرفر
            if ($product->img) {
                Storage::disk('public')->delete($product->img);
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
        $products = QueryBuilder::for(product::query()->select(['id', 'titel', 'img', 'price', 'stock']))
            ->allowedFilters([
                'titel',
                'brand',
                AllowedFilter::callback('categorie.name', function ($query, $value) {
                    $decodedValue = urldecode($value);
                    $query->whereHas('categorie', function ($q) use ($decodedValue) {
                        $q->where('name', 'like', "%{$decodedValue}%");
                    });
                }),
            ])
            ->paginate(10)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'result' => $products,
        ]);
    }

    public function search8(Request $request)
    {
        $products = QueryBuilder::for(product::query()->select([
            'id',
            'titel',
            'img',
            'price',
            'description',
            'votes',
            'inCount',
            'Counttype',
            'inCounttype',
            'discount',
            'brand',
            'stock',
        ]))
            ->allowedFilters([
                'titel',
                'brand',
                AllowedFilter::callback('categorie.name', function ($query, $value) {
                    $decodedValue = urldecode($value);
                    $query->whereHas('categorie', function ($q) use ($decodedValue) {
                        $q->where('name', 'like', "%{$decodedValue}%");
                    });
                }),
            ])
            ->paginate(10)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'result' => $products,
        ]);
    }

    public function searchByCategory(Request $request)
    {
        // تأمين وتطهير المدخلات من نصوص سكريبت خبيثة (XSS Protection)
        $query = strip_tags($request->input('q'));

        $category = categorie::where('name', 'like', "%{$query}%")
            ->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الفئة المطلوبة',
            ], 404);
        }

        $products = $category->product()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'banner' => $category->banner,
            ],
            'products' => $products,
        ], 200);
    }

    public function export()
    {
        $products = product::with('page')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['ID', 'Title', 'Description', 'Votes', 'InCount', 'URL', 'Brand', 'Price', 'Stock', 'Category ID', 'Page ID', 'Counttype', 'inCounttype', 'discount'],
        ]);

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

        $fileName = 'products.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/'.$fileName);
        $writer->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        // تأمين فحص ملف الـ Excel قبل قراءته للتأكد من هويته ونوعه
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);
        $file = $request->file('file')->getRealPath();

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach (array_slice($rows, 1) as $row) {
            // تأمين البيانات القادمة من الـ Excel وتحويلها للأنواع المناسبة لمنع الـ SQL Injection والبيانات التالفة
            $id = isset($row[0]) ? (int)$row[0] : null;
            
            product::updateOrCreate(
                ['id' => $id],
                [
                    'titel' => strip_tags($row[1] ?? ''),
                    'description' => strip_tags($row[2] ?? ''),
                    'votes' => isset($row[3]) ? (float)$row[3] : 0,
                    'inCount' => strip_tags($row[4] ?? ''),
                    'url' => filter_var($row[5] ?? '', FILTER_VALIDATE_URL) ? $row[5] : '',
                    'brand' => strip_tags($row[6] ?? ''),
                    'price' => isset($row[7]) ? (float)$row[7] : 0,
                    'stock' => isset($row[8]) ? (int)$row[8] : 0,
                    'category_id' => isset($row[9]) ? (int)$row[9] : null,
                    'page_id' => isset($row[10]) ? (int)$row[10] : null,
                    'Counttype' => strip_tags($row[11] ?? ''),
                    'inCounttype' => strip_tags($row[12] ?? ''),
                    'discount' => isset($row[13]) ? (float)$row[13] : null,
                ]
            );
        }

        return response()->json(['message' => 'تم استيراد المنتجات بنجاح']);
    }
}
