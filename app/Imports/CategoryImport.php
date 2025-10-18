<?php

namespace App\Imports;


use App\Models\categorie;
use Maatwebsite\Excel\Concerns\ToModel;

class CategoryImport implements ToModel
{
    public function model(array $row)
    {
        // تخطى الصف الأول لو فيه عناوين
        if ($row[0] === 'id' || $row[0] === null) {
            return null;
        }

        return new categorie([
            'name'        => $row[1],
            'slug'        => $row[2],
            'description' => $row[3],
            'img'         => $row[4] ?? null,
        ]);
    }
}
