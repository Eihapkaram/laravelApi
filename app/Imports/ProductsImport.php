<?php

namespace App\Imports;

use App\Models\product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new product([
            'titel'       => $row['titel'] ?? null,
            'description' => $row['description'] ?? null,
            'votes'       => $row['votes'] ?? 0,
            'inCount'     => $row['incount'] ?? null,
            'url'         => $row['url'] ?? null,
            'brand'       => $row['brand'] ?? null,
            'img'         => $row['img'] ?? null,
            'images_url'  => isset($row['images_url']) ? json_encode(explode(',', $row['images_url'])) : null,
            'price'       => $row['price'] ?? 0,
            'stock'       => $row['stock'] ?? 0,
            'category_id' => $row['category_id'] ?? null,
            'page_id'     => $row['page_id'] ?? null,
        ]);
    }
}
