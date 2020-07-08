<?php

namespace App\Exports;
  
use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoriesExport implements FromCollection, WithHeadings
{
   
    public function collection()
    {
        return Category::all();
    }

    public function headings(): array
    {

        return [
            'id',
            'type',
            'title',
            'description',
            'slug',
            'status',
            'created_by',
            'last_updated_by',
            'deleted_at',
            'created_at',
            'updated_at',
        ];

    }
}