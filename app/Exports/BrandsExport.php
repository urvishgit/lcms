<?php

namespace App\Exports;
  
use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BrandsExport implements FromCollection, WithHeadings
{
   
    public function collection()
    {
        return Brand::all();
    }

    public function headings(): array
    {

        return [
            'id',
            'title',
            'description',
            'logo',
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