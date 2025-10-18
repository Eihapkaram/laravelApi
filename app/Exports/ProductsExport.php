<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::select(
            'id',
            'titel',
            'description',
            'votes',
            'inCount',
            'url',
            'brand',
            'img',
            'images_url',
            'price',
            'stock',
            'category_id',
            'page_id',
            'created_at',
            'updated_at'
        )->get();
    }

    public function headings(): array
    {
        return [
            'id',
            'titel',
            'description',
            'votes',
            'inCount',
            'url',
            'brand',
            'img',
            'images_url',
            'price',
            'stock',
            'category_id',
            'page_id',
            'created_at',
            'updated_at',
        ];
    }
}
