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
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

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
            'inCount'=> 'required',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
            'price' => 'required',
            'stock' => 'required',
            'category_id' => 'required|min:1',
            'page_id' => 'required|min:1',
            'brand' => 'required',
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
            'inCount' => $request->inCount
        ]);
         $user = auth()->user();
         if ($product) {
    // جيب كل المستخدمين اللي رولهم أدمن
    $admins = User::where('role', 'customer')->get();

    // ابعت الإشعار ليهم
    Notification::send($admins, new NewProduct($user,$product));
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

    public function edit(product $product)
    {
    }

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
        ]);

        // تحديث الصور الإضافية
        $product = product::findOrFail($id);

        if ($request->hasFile('images_url')) {
            foreach ($product->images as $oldImage) {
                Storage::disk('public')->delete($oldImage->path);
                $oldImage->delete();
            }

            foreach ($request->file('images_url') as $imageUP) {
                $imageName = time().'_'.uniqid().'.'.$imageUP->getClientOriginalExtension();
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
    public function export()
    {
        return Excel::download(new ProductsExport, 'products.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new ProductsImport, $request->file('file'));

        return response()->json(['message' => 'Products imported successfully']);
    }

}
