<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Product::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Description',
            'Votes',
            'In Count',
            'URL',
            'Brand',
            'Main Image',
            'Images URLs',
            'Price',
            'Stock',
            'Category ID',
            'Page ID',
            'Created At',
            'Updated At',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->titel,
            $product->description,
            $product->votes,
            $product->inCount,
            $product->url,
            $product->brand,
            $product->img,
            $product->images_url ? json_encode($product->images_url) : '',
            $product->price,
            $product->stock,
            $product->category_id,
            $product->page_id,
            $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : '',
            $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
