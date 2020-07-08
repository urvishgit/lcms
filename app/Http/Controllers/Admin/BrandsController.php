<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BrandsExport;

use App\Services\AdminModulePermissionCheck;
use App\Services\AdminToggleStatus;
use App\Services\AdminPagination;
use App\Services\Slug;

use App\Http\Requests\Brand\CreateBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;

use App\Models\Brand;

class BrandsController extends AdminController
{
    private $checkModulePermission;
    
    private $module = 'brand';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AdminModulePermissionCheck $permissionCheckService)
    {
        $this->middleware('auth');
        $this->indexPath = 'admin.brands.index';
        $this->checkModulePermission = $permissionCheckService;
    }

    public function index(Request $request, AdminPagination $paginationService)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $query = $request->query('q');
        $orderBy = $request->query('orderBy') ?: 'id';
        $order = $request->query('order') ?: 'desc';
        $pageNo = $request->query('pageNo') ?:1;

        $data = $order == 'asc' ? Brand::with(['createdBy'])->Search($query)->get()->sortBy($orderBy) : Brand::with(['createdBy'])->Search($query)->get()->sortByDesc($orderBy);
       
        $brands = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.brands.data')->with('brands', $brands)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('brands',$brands);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        return view('admin.brands.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateBrandRequest $request)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $logo = $request->hasFile('logo') ? $request->logo->store('brands') : null;
        
        Brand::create([
            'title' => $request->title,
            'description' => $request->description,
            'logo'=> $logo,
            'status' => $request->status ?: 0,
            'slug' => $request->slug,
            'created_by' => auth()->user()->id,
            'last_updated_by' => auth()->user()->id,
        ]);

        session()->flash('success', 'Brand added sucessfully.');

        return redirect(route($this->indexPath));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Brand $brand)
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        return view('admin.brands.show')->with('brand', $brand);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Brand $brand)
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        return view('admin.brands.create')->with('brand', $brand);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $data = $request->only(['title', 'description', 'logo', 'status', 'slug', 'last_updated_by']);

        if($request->hasFile('logo')) {
            $logo = $request->logo->store('brands');
            if($brand->logo){
                $brand->deleteBrandLogo();
            }
            $data['logo'] = $logo;
        }

        $data['status'] = $request->status ?: 0;

        $data['last_updated_by'] = auth()->user()->id;
        
        $brand->update($data);

        session()->flash('success', 'Brand updated sucessfully.');

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

        $brand = Brand::withTrashed()->where('id', $id)->firstOrFail();
        
        if($brand) {
            if($brand->trashed()) {
                $brand->forceDelete();
            } else {
                $brand->delete();
            }
            session()->flash('success', 'Brand deleted sucessfully.');
        } else {
            session()->flash('error', 'Brand not found.');
        }
        return redirect(route($this->indexPath));
    }

    /**
     * Display a list of all posts
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
        
        $data = $order == 'asc' ? Brand::with(['createdBy'])->onlyTrashed()->Search($query)->get()->sortBy($orderBy) : Brand::with(['createdBy'])->onlyTrashed()->Search($query)->get()->sortByDesc($orderBy);

        $brands = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.brands.data')->with('brands', $brands)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('brands', $brands);
    }

    /**
     * Restore a brand
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $brand = Brand::withTrashed()->where('id', $id)->firstOrFail();
        
        if($brand){

            $brand->restore();

            session()->flash('success', 'Brand restored sucessfully.');
        }
        return redirect()->back();
    }

    /**
     * Change status for a brand
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(Brand $brand, AdminToggleStatus $changeRecordStatus) 
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        
        $changeRecordStatus->toggleStatus($brand);
    }

    /**
     * export post data
     *
     * @return csv file.
     */
    public function export() 
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return Excel::download(new CategoriesExport, 'brands.csv');
    }
    
    /**
     * check post slug from title
     *
     * @return response post slug.
     */
    public function checkSlug(Request $request, Slug $slug)
    {
        $slug = $slug->createSlug(Brand::class, $request->title, $request->id);
        return response()->json(['slug' => $slug]);
    }
}
