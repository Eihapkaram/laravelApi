<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new User([
            'name'       => $row['name'] ?? null,
            'last_name'  => $row['last_name'] ?? null,
            'email'      => $row['email'] ?? null,
            'phone'      => $row['phone'] ?? null,
            'password'   => isset($row['password']) ? Hash::make($row['password']) : null,
            'role'       => $row['role'] ?? 'customer',
            'img'        => $row['img'] ?? null,
        ]);
    }
}
