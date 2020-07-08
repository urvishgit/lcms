<?php

namespace App\Exports;
  
use App\Models\Tag;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TagsExport implements FromCollection, WithHeadings
{
   
    public function collection()
    {
        return Tag::all();
    }

    public function headings(): array
    {

        return [
            'id',
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