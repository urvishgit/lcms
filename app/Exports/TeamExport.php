<?php

namespace App\Exports;
  
use App\Models\Team;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeamExport implements FromCollection, WithHeadings
{
   
    public function collection()
    {
    	return Team::with(['category','createdBy'])->orderBy('id', 'desc')->get();

    }

    public function headings(): array
    {

        return [
            'id',
            'category_id',
            'title',
            'description',
            'content',
            'image',
            'job_title',
            'address',
            'email',
            'tel',
            'mobile',
            'linkedin',
            'seo_title',
            'seo_description',
            'slug',
            'status',
            'published_at',
            'created_by',
            'last_updated_by',
            'deleted_at',
            'created_at',
            'updated_at',
        ];

    }
}