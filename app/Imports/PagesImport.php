<?php

namespace App\Imports;

use App\Models\Page;
use Maatwebsite\Excel\Concerns\ToModel;

class PagesImport implements ToModel
{
    public function model(array $row)
    {
        return new Page([
            'title' => $row[0],
            'slug' => $row[1],
            'content' => $row[2] ?? null,
            'meta_title' => $row[3] ?? null,
            'meta_description' => $row[4] ?? null,
        ]);
    }
}
