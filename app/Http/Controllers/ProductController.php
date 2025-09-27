<?php

namespace App\Http\Controllers;

use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = product::with('productReviwes', 'images')->get();

        return response()->json([
            'success' => true,
            'message' => 'all products',
            'products' => $data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $request->validate(
            [
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
            ]
        );

        if (! $request) {
            return response()->json(['error' => 'faild in create']);
        }

        // رفع الصورة
        $imagePath = null;
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('products', $image, 'public');
            // => هيتخزن في storage/app/public/products
        }

        $product = Product::create([
            'titel' => $request->titel,
            'description' => $request->description,
            'votes' => $request->votes,
            'url' => $request->url,
            'img' => $path, // مسار الصورة
            'price' => $request->price,
            'stock' => $request->stock,
            'category_id' => $request->category_id,
            'images_url' => $imagePath,
            'page_id' => $request->page_id,
            'brand' => $request->brand,
        ]);
        // إضافة الصور في جدول منفصل
        if ($request->hasFile('images_url')) {
            foreach ($request->file('images_url') as $image) {
                $imageup = $image->getClientOriginalName();
                $path = $image->storeAs('products', $imageup, 'public');
                $product->images()->create(['path' => $path]);
            }
        }

        $data = Product::with('productReviwes', 'images', 'page')->get();

        return response()->json([
            'sucsse' => 'true',
            'message' => 'add item done',
            'data' => $data,

        ]);
    }

   /**
    * Display the specified resource.
    *
    * @param  \App\Models\product  $product
    * @return \Illuminate\Http\Response
    */
   public function show($id)
   {
       $product = Product::find($id);
       $categorie = $product->with('categorie');
       $data = Product::with('productReviwes', 'images', 'page', 'categorie')->find($id);
       if (is_null($product)) {
           return response()->json([
               'fail' => 'feild',
               'message' => 'product not found',
           ]);
       }

       return response()->json([
           'succss' => 'true',
           'message' => 'product is found',
           'data' => $data,
           'categorie' => $categorie,
       ]);
   }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(product $product)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, product $product, $id)
    {
        $request->validate(
            [
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
            ]
        );

        if (! $request) {
            return response()->json(['error' => 'faild edit']);
        }
        $pro = Product::findOrFail($id);

        // رفع الصورة
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('products', $image, 'public');
            // => هيتخزن في storage/app/public/products
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
        // إضافة الصور في جدول منفصل
        $product = Product::findOrFail($id);

        if ($request->hasFile('images_url')) {
            // امسح الصور القديمة من جدول images ومن storage
            foreach ($product->images as $oldImage) {
                Storage::disk('public')->delete($oldImage->path);
                $oldImage->truncate();
            }

            foreach ($request->file('images_url') as $imageUP) {
                $imageName = time().'_'.uniqid().'.'.$imageUP->getClientOriginalName();
                $path = $imageUP->storeAs('products', $imageName, 'public');

                $product->images()->create([
                    'path' => $path,
                ]);
            }
        }

        return response()->json([
            'sucsse' => 'true',
            'message' => 'edit item done',
            'imgupdate' => $product->load('images'),

        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if ($product) {
            $product->delete();

            return response()->json([
                'sucsse' => 'true',
                'data' => $product->load('images'),
                'message' => 'delete item done',
            ]);
        } else {
            return response()->json([
                'sucsse' => 'false',
                'message' => 'not find item id',
            ]);
        }
    }
}
