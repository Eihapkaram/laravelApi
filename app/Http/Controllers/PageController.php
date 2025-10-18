<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use App\Imports\PagesImport;
use App\Exports\PagesExport;
use Maatwebsite\Excel\Facades\Excel;

class PageController extends Controller
{
    public function AddPage(Request $request)
    {
        $request->validate([
            'slug' => 'required',
        ]);


        Page::create(['slug' => $request->slug]);


        $pro = Page::get();

        return response()->json([
            'massege' => 'add page done',
            'pro' => $pro,
        ]);
    }

    public function showPageProduct()
    {
        $pro = Page::with('pageproducts')->get();

        return response()->json([
            'massege' => 'show all page prodcts',
            'pro' => $pro,
        ]);
    }

    public function DeletePage($id)
    {
        $pro = Page::find($id);
        $pro->delete();

        return response()->json([
            'massege' => 'delete Page is done',
            'data' => Page::get(),
        ]);
    }

    public function UpdatePage(Request $request, $id)
    {
        $request->validate(['slug' => 'required']);
        if (! $request || ! $id) {
            return response()->json([
                'massege' => 'update Page not done',
            ]);
        }
        $pro = Page::find($id);
        $pro->update(['slug' => $request->slug]);

        return response()->json([
            'massege' => 'update Page is done',
            'data' => Page::get(),
        ]);
    }
    // ✅ رفع ملف Excel
    public function import(Request $request)
    {
        Excel::import(new PagesImport, $request->file('file'));
        return response()->json(['message' => 'تم استيراد الصفحات بنجاح']);
    }

    // ✅ تصدير ملف Excel
    public function export()
    {
        return Excel::download(new PagesExport, 'pages.xlsx');
    }
}
