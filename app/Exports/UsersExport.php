<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * إرجاع البيانات
     */
    public function collection()
    {
        return User::all();
    }

    /**
     * العناوين في أول صف
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Last Name',
            'Email',
            'Phone',
            'Email Verified At',
            'Role',
            'Img',
            'Last Seen',
            'Created At',
            'Updated At'
        ];
    }

    /**
     * تحديد كيفية كتابة كل صف في Excel
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->last_name,
            $user->email,
            "'".$user->phone, // نجمة صغيرة لجعل الرقم كنص (يحافظ على الأصفار)
            $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : '',
            $user->role,
            $user->img,
            $user->last_seen ? $user->last_seen->format('Y-m-d H:i:s') : '',
            $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '',
            $user->updated_at ? $user->updated_at->format('Y-m-d H:i:s') : ''
        ];
    }
}
