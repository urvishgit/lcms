<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;

use App\Services\AdminitratorModulePermissionCheck;

use APp\Models\Setting;

class SettingsController extends AdminController
{
    private $checkModulePermission;
    
    private $module = 'settings';
    
    public function __construct(AdminitratorModulePermissionCheck $permissionCheckService)
    {
        $this->indexPath = 'admin.settings.index';
        $this->checkModulePermission = $permissionCheckService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view($this->indexPath);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        
        if ($request->has('site_logo')) {
            
            $site_logo = $request->site_logo->store('settings/logo');

            if (config('settings.site_logo') != null) {
                Setting::deleteSettingImage(config('settings.site_logo'));
            }
            Setting::set('site_logo', $site_logo);

        } elseif ($request->has('site_favicon')) {
            
            $site_favicon = $request->site_favicon->store('settings/favicon');

            if (config('settings.site_favicon') != null) {
                Setting::deleteSettingImage(config('settings.site_favicon'));
            }
           Setting::set('site_favicon', $site_favicon);

        } else {

            $keys = $request->except('_token');

            foreach ($keys as $key => $value)
            {
                Setting::set($key, $value);
            }
        }

        session()->flash('success', 'Settings updated sucessfully.');

        return redirect(route($this->indexPath));

    }
}
