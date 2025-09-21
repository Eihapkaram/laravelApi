<?php

namespace App\Http\Controllers;

use App\Models\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = product::all();
        return response()->json([
            'success'=>true,
            'message'=> "all products",
            'products'=>$data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $interdata = $request->all();
        $validate = Validator::make($interdata,[
        'titel'=> 'required',
        'description'=> 'required',
        'votes'=> 'required',
        'url'=> 'required',
        'img'=> 'required',
        'price'=> 'required',
        'stock'=> 'required',
        'category_id'=> 'required|min:1',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'faild in create']);
        };
        $product = Product::create($interdata);
        return response()->json([
            'sucsse'=>'true',
            'data'=>$product,
            'message'=> "add item done",
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
        if(is_null($product)) {
return response()->json([
'fail'=>"feild",
'message'=>"product not found",
]);
        };
        return response()->json([
       'succss'=>"true",
        'message'=>"product is found",
        'data'=> $product,
]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(product $product)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, product $product,$id)
    {
         $interdata = $request->all();
        $validate = Validator::make($interdata,[
            'titel'=> 'required',
        'description'=> 'required',
        'votes'=> 'required',
        'url'=> 'required',
        'img'=> 'required',
        'price'=> 'required',
        'stock'=> 'required',
        'category_id'=> 'required|min:1',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'faild edit']);
        };
        $pro = Product::find($id);
        $pro->update($interdata);
        return response()->json([
            'sucsse'=>'true',
            'data'=>$pro,
            'message'=> "edit item done",
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
        $product->delete();
        return response()->json([
            'sucsse'=>'true',
            'data'=>$product,
            'message'=> "delete item done",
    ]);
    }
}
