<?php

namespace App\Exports;

use App\Models\categorie;
use Maatwebsite\Excel\Concerns\FromCollection;

class CategoryExport implements FromCollection
{
    public function collection()
    {
        return categorie::all(['id', 'name', 'slug', 'description', 'img', 'created_at']);
    }
}
