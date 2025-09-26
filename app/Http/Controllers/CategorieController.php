<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\categorie;

class CategorieController extends Controller
{
    public function AddCate(Request $request) {
$request->validate([
    'name'=>'required'
    ,'slug'=>'required'
    ,'description'=>'required'
]);
 categorie::create([
     'name'=>$request->name
    ,'slug'=>$request->slug
    ,'description'=>$request->description
]);
$pro=categorie::all();
return response()->json([
            'massege'=>'add categore done',
            'pro' => $pro
        ]);
    }
    public function showCateProduct() {
        $pro = Categorie::with('product')->get();
        return response()->json([
            'massege'=>'show all categore prodcts',
            'pro' => $pro
        ]);
    }
    public function DeleteCate($id) {
        $pro=categorie::find($id);
        $pro->delete();
return response()->json([
    'massege' =>'delete categorie is done',
    'data' => categorie::get()
]);
    }
    public function UpdateCate(Request $request ,$id) {
        $request->validate([
    'name'=>'required'
    ,'slug'=>'required'
    ,'description'=>'required'
]);
if (!$request||!$id) {
     return response()->json([
    'massege' =>'update categorie not done'
]);
}
        $pro=categorie::find($id);
        $pro->update([
            'name'=>$request->name
    ,'slug'=>$request->slug
    ,'description'=>$request->description
        ]);
        return response()->json([
    'massege' =>'update categorie is done',
    'data' => categorie::get()
]);

    }
}
