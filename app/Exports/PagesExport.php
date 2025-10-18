<?php

namespace App\Exports;

use App\Models\Page;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PagesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * إرجاع البيانات
     */
    public function collection()
    {
        return Page::all();
    }

    /**
     * العناوين في أول صف
     */
    public function headings(): array
    {
        return [
            'ID',
            'Slug',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * تحديد كيفية كتابة كل صف في Excel
     */
    public function map($page): array
    {
        return [
            $page->id,
            $page->slug,
            $page->created_at ? $page->created_at->format('Y-m-d H:i:s') : '',
            $page->updated_at ? $page->updated_at->format('Y-m-d H:i:s') : ''
        ];
    }
}
