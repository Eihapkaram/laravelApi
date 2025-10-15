<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\categorie;

class CategorieController extends Controller
{
    public function AddCate(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
            'description' => 'required'
        ]);
        // رفع الصورة
        $imagePath = null;
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('categories', $image, 'public');
            // => هيتخزن في storage/app/public/products
        }

        categorie::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'img' => $path,
        ]);
        $pro = categorie::all();
        return response()->json([
            'massege' => 'add categore done',
            'pro' => $pro
        ]);
    }
    public function showCateProduct()
    {
        $pro = categorie::with('product')->get();
        return response()->json([
            'massege' => 'show all categore prodcts',
            'pro' => $pro
        ]);
    }
    public function DeleteCate($id)
    {
        $pro = categorie::find($id);
        $pro->delete();
        return response()->json([
            'massege' => 'delete categorie is done',
            'data' => categorie::get()
        ]);
    }
    public function UpdateCate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required',
            'description' => 'required',
        ]);

        if (!$request || !$id) {
            return response()->json([
                'massege' => 'update categorie not done'
            ]);
        }
        // رفع الصورة
        $imagePath = null;
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('categories', $image, 'public');
            // => هيتخزن في storage/app/public/products
        }

        $pro = categorie::find($id);
        $pro->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'img' => $path,
        ]);
        return response()->json([
            'massege' => 'update categorie is done',
            'data' => categorie::get()
        ]);
    }
}
