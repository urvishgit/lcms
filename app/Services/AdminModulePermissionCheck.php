<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class AdminModulePermissionCheck
{
    
    /**
     * @param string $module_slug
     * @return admin home
     */
    public function checkModulePermission(string $module_slug)
    {
        if( !auth()->user()->isAdmin() || !auth()->user()->hasModuleAllow($module_slug) ) {
            
            session()->flash("error", "Sorry, you don't have permission to view ".$module_slug." page.");
            
            return redirect(route('admin.home'))->send();
        }
        return;
    }
}