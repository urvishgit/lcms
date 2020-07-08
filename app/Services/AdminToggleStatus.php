<?php

namespace App\Services;

class AdminToggleStatus
{
    
    /**
     * @param string $module_slug
     * @return home
     */
    public function toggleStatus($record)
    {
        if(!$record) return false;

        $data['status'] = $record->status == 1 ? 0 : 1;
        $data['last_updated_by'] = auth()->user()->id;

        $record->update($data);

        session()->flash('success', 'Status change sucessfully.');
        
        return redirect()->back()->send();
    }
}