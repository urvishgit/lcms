<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ModulesExport;

use App\Services\AdminitratorModulePermissionCheck;
use App\Services\AdminToggleStatus;
use App\Services\AdminPagination;
use App\Services\Slug;

use App\Http\Requests\Module\CreateModuleRequest;
use App\Http\Requests\Module\UpdateModuleRequest;

use App\Models\User;
use App\Models\Module;

class ModulesController extends AdminController
{
    private $checkModulePermission;
    
    private $module = 'modules';
    
    public function __construct(AdminitratorModulePermissionCheck $permissionCheckService)
    {
        $this->indexPath = 'admin.modules.index';
        $this->checkModulePermission = $permissionCheckService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, AdminPagination $paginationService)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $query = $request->query('q');
        $orderBy = $request->query('orderBy') ?: 'id';
        $order = $request->query('order') ?: 'desc';
        $pageNo = $request->query('pageNo') ?:1;

        $data = $order == 'asc' ? Module::Search($query)->get()->sortBy($orderBy) : Module::Search($query)->get()->sortByDesc($orderBy);
       
        $modules = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.modules.data')->with('modules', $modules)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('modules',$modules);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        return view('admin.modules.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateModuleRequest $request)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $module = Module::create([
            'title' => $request->title,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'icon' => $request->icon,
            'route' => $request->route,
            'trash' => $request->trash ?: 0,
            'trash_route' => $request->trash_route,
            'order' => $request->order,
            'is_administrator_module' => $request->is_administrator_module ?: 0,
            'slug' => $request->slug,
            'status' => $request->status ?: 0,
            'published_at' => $request->published_at,
        ]);

        $user = User::find(auth()->user()->id);

        if($module->id) {
            $user->modules()->attach($module->id);
        }

        session()->flash('success', 'Module added sucessfully.');

        return redirect(route($this->indexPath));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Module $module)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.modules.show')->with('module', $module);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Module $module)
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        return view('admin.modules.create')->with(['module' => $module]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateModuleRequest $request, Module $module)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $data = $request->only([
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
        ]);

        $data['trash'] = $request->trash ?: 0;
        $data['is_administrator_module'] = $request->is_administrator_module ?: 0;
        $data['status'] = $request->status ?: 0;

        $module->update($data);

        session()->flash('success', 'Module updated sucessfully.');

        return redirect(route($this->indexPath));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {   
        $this->checkModulePermission->checkModulePermission($this->module);
        
        $module = Module::withTrashed()->where('id', $id)->firstOrFail();

        if($module){
            if($module->trashed()){
                $module->users()->detach();
                $module->forceDelete();
            } else {
                $module->delete();
            }
            session()->flash('success', 'Module deleted sucessfully.');
        } else {
            session()->flash('error', 'Module not found.');
        }
        
        return redirect(route($this->indexPath));
    }

    /**
     * Display a list of all modules
     *
     * @return \Illuminate\Http\Response
     */
    public function trashed(Request $request, AdminPagination $paginationService)
    {   
        $this->checkModulePermission->checkModulePermission($this->module);

        $query = $request->query('q');
        $orderBy = $request->query('orderBy') ?: 'id';
        $order = $request->query('order') ?: 'desc';
        $pageNo = $request->query('pageNo') ?:1;
        
        $data = $order == 'asc' ? Module::onlyTrashed()->Search($query)->get()->sortBy($orderBy) : Module::onlyTrashed()->Search($query)->get()->sortByDesc($orderBy);

        $modules = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.modules.data')->with('modules', $modules)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('modules',$modules);
    }

    /**
     * Restore a module
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $module = Module::withTrashed()->where('id', $id)->firstOrFail();
        
        if($module){

            $module->restore();

            session()->flash('success', 'Module restored sucessfully.');
        }
        return redirect()->back();
    }

    /**
     * Change status for a module
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(Module $module) 
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        
        $data['status'] = $module->status == 1 ? 0 : 1;
        $data['last_updated_by'] = auth()->user()->id;

        $module->update($data);

        session()->flash('success', 'Status change sucessfully.');
        
        return redirect()->back();
    }

    /**
     * export module data
     *
     * @return csv file.
     */
    public function export() 
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return Excel::download(new ArticlesExport, 'modules.csv');
    }

    /**
     * check module slug from title
     *
     * @return response module slug.
     */
    public function checkSlug(Request $request, Slug $slug)
    {
        $slug = $slug->createSlug(Module::class, $request->title, $request->id);
        return response()->json(['slug' => $slug]);
    }
    
}
