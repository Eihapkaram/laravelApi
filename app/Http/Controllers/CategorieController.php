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
}
