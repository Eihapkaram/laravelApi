<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     // ✅ تصدير البيانات إلى Excel
    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // رؤوس الأعمدة
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Slug');
        $sheet->setCellValue('C1', 'Created At');

        // البيانات
        $pages = Page::all();
        $row = 2;
        foreach ($pages as $page) {
            $sheet->setCellValue('A' . $row, $page->id);
            $sheet->setCellValue('B' . $row, $page->slug);
            $sheet->setCellValue('C' . $row, $page->created_at);
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
            if ($index == 1) continue;

            $slug = $row['B'] ?? null;
            if ($slug) {
                Page::create(['slug' => $slug]);
            }
        }

        return response()->json(['message' => 'تم استيراد الصفحات بنجاح']);
    }
}
