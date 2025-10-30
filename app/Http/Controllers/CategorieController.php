<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\categorie;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CategorieController extends Controller
{
    public function AddCate(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
            'banner' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
            'description' => 'required',
            'page_id' => 'nullable',
        ]);
        // رفع الصورة
        $imagePath = null;
        if ($request->hasFile('img')) {
            $image = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('categories', $image, 'public');
            // => هيتخزن في storage/app/public/products
        }
        // رفع الصورة الرئيسية
        $path2 = null;
        if ($request->hasFile('banner')) {
            $image2 = $request->file('banner')->getClientOriginalName();
            $path2 = $request->file('banner')->storeAs('categorebanner', $image2, 'public');
        }


        categorie::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'img' => $path,
            'banner' => $path2,
            'page_id' => $request->page_id,
        ]);
        $pro = categorie::all();
        return response()->json([
            'massege' => 'add categore done',
            'pro' => $pro
        ]);
    }
    public function showCateProduct()
    {
        $pro = categorie::with('product', 'page')->get();
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
        // رفع الصورة الرئيسية
        $path2 = null;
        if ($request->hasFile('banner')) {
            $image2 = $request->file('banner')->getClientOriginalName();
            $path2 = $request->file('banner')->storeAs('categorebanner', $image2, 'public');
        }

        $pro = categorie::find($id);
        $pro->update([
            'name' => $request->name ?? $pro->name,
            'slug' => $request->slug ?? $pro->slug,
            'description' => $request->description ?? $pro->description,
            'img' => $path ?? $pro->img,
            'banner' => $path2 ?? $pro->banner,
        ]);
        return response()->json([
            'massege' => 'update categorie is done',
            'data' => categorie::get()
        ]);
    }
    // ✅ تصدير البيانات إلى Excel
    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // رؤوس الأعمدة
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Slug');
        $sheet->setCellValue('D1', 'Description');
        $sheet->setCellValue('E1', 'Img');
        $sheet->setCellValue('F1', 'Banner');
        $sheet->setCellValue('G1', 'page_id');

        // جلب البيانات
        $categories = Categorie::all();
        $row = 2;

        foreach ($categories as $cat) {
            $sheet->setCellValue('A' . $row, $cat->id);
            $sheet->setCellValue('B' . $row, $cat->name);
            $sheet->setCellValue('C' . $row, $cat->slug);
            $sheet->setCellValue('D' . $row, $cat->description);
            $sheet->setCellValue('E' . $row, $cat->img);
            $sheet->setCellValue('F' . $row, $cat->banner);
            $sheet->setCellValue('G' . $row, $cat->page_id);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'categories.xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    // ✅ استيراد البيانات من Excel
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // تخطي الصف الأول (الرؤوس)
        foreach (array_slice($rows, 1) as $row) {
            if (!empty($row[1])) { // name موجود
                Categorie::updateOrCreate(
                    ['slug' => $row[2]], // مفتاح فريد
                    [
                        'name' => $row[1],
                        'description' => $row[3] ?? '',
                        'img' => $row[4] ?? null,
                        'banner' => $row[5] ?? null,
                        'page_id' => $row[6] ?? null,
                    ]
                );
            }
        }

        return response()->json(['message' => 'Categories imported successfully ✅']);
    }
}
