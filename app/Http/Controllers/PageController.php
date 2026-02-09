<?php

namespace App\Http\Controllers;

use App\Models\categorie;
use App\Models\Page;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PageController extends Controller
{
    public function AddPage(Request $request)
    {
        $request->validate([
            'slug' => 'required',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
        ]);
        // رفع الصورة
        $imagePath = null;
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('pages', $image, 'public');
            // => هيتخزن في storage/app/public/products
        }

        Page::create([
            'slug' => $request->slug,
            'img' => $path,
        ]);

        $pro = Page::get();

        return response()->json([
            'massege' => 'add page done',
            'pro' => $pro,
        ]);
    }

    public function showPageProduct()
    {
        $pro = Page::all();

        return response()->json([
            'massege' => 'show all page prodcts',
            'pro' => $pro,
        ]);
    }

    public function getCategoriesByPageSlug($slug)
    {
        $page = Page::where('slug', $slug)
            ->with('categories')
            ->first();

        if (! $page) {
            return response()->json([
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json($page->categories);
    }

    public function getProductsByCategorySlug($slug)
    {
        $category = categorie::where('slug', $slug)
            ->with('product')
            ->first();

        if (! $category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Products by category slug',
            'products' => $category->product,
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

        if (! $request || ! $id) {
            return response()->json([
                'massege' => 'update Page not done',
            ]);
        }
        $pro = Page::find($id);
        $imagePath = null;
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('pages', $image, 'public');
            // => هيتخزن في storage/app/public/products
        }
        $pro->update(['slug' => $request->slug ?? $pro->slug, 'img' => $path ?? $pro->img]);

        return response()->json([
            'massege' => 'update Page is done',
            'data' => Page::get(),
        ]);
    }

    public function search(Request $request)
    {
        $products = QueryBuilder::for(Page::query())
            ->allowedFilters([
                'slug',
            ])
            ->with(['categories', 'pageproducts'])
            ->get();

        return response()->json([
            'success' => true,
            'result' => $products,
        ], 200);
    }

    // ✅ تصدير البيانات إلى Excel
    public function export()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // رؤوس الأعمدة
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Slug');
        $sheet->setCellValue('C1', 'img');
        $sheet->setCellValue('D1', 'Created At');

        // البيانات
        $pages = Page::all();
        $row = 2;
        foreach ($pages as $page) {
            $sheet->setCellValue('A'.$row, $page->id);
            $sheet->setCellValue('B'.$row, $page->slug);
            $sheet->setCellValue('C'.$row, $page->img);
            $sheet->setCellValue('D'.$row, $page->created_at);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        // تحميل مباشر للملف في المتصفح
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $response->headers->set('Content-Disposition', 'attachment;filename="pages.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    // ✅ استيراد البيانات من Excel
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        $path = $request->file('file')->getRealPath();

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // تخطي أول صف (العناوين)
        foreach ($rows as $index => $row) {
            if ($index == 1) {
                continue;
            }

            $slug = $row['B'] ?? null;
            if ($slug) {
                Page::create(['slug' => $slug]);
            }
        }

        return response()->json(['message' => 'تم استيراد الصفحات بنجاح']);
    }
}
