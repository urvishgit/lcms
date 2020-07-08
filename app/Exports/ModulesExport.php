<?php

namespace App\Exports;
  
use App\Models\Module;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ModulesExport implements FromCollection, WithHeadings
{
   
    public function collection()
    {
        $modules = Module::all();
        return $users->map->only(['id',
            'title',
            'display_name',
            'description', 
            'icon', 
            'route', 
            'trash', 
            'trash_route', 
            'order',
            'is_administrator_module',
            'slug', 
            'status', 
            'published_at',  
            'created_at',
            'updated_at']);
    }

    public function headings(): array
    {

        return [
            'id',
            'title',
            'display_name',
            'description', 
            'icon', 
            'route', 
            'trash', 
            'trash_route', 
            'order',
            'is_administrator_module',
            'slug', 
            'status', 
            'published_at',  
            'created_at',
            'updated_at',
        ];

    }
}