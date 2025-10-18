<?php

namespace App\Exports;

use App\Models\Page;
use Maatwebsite\Excel\Concerns\FromCollection;

class PagesExport implements FromCollection
{
    public function collection()
    {
        // هيجيب كل الصفحات من قاعدة البيانات
        return Page::all();
    }
}
