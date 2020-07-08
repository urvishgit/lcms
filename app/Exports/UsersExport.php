<?php

namespace App\Exports;
  
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{
   
    public function collection()
    {
        $users = User::all();
        return $users->map->only(['id', 'name', 'email', 'email_verified_at', 'role', 'about', 'status', 'created_at', 'updated_at']);
    }

    public function headings(): array
    {

        return [
            'id',
            'name',
            'email',
            'email_verified_at',
            'role',
            'about',
            'status',
            'created_at',
            'updated_at',
        ];

    }
}