<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CasestudiesExport;

use App\Services\AdminModulePermissionCheck;
use App\Services\AdminToggleStatus;
use App\Services\AdminPagination;
use App\Services\Slug;

use App\Http\Requests\Casestudy\CreateCasestudiesRequest;
use App\Http\Requests\Casestudy\UpdateCasestudiesRequest;

use App\Models\Category;
use App\Models\Casestudy;

class CasestudiesController extends AdminController
{
    private $checkModulePermission;
    
    private $module = 'casestudy';
    
    public function __construct(AdminModulePermissionCheck $permissionCheckService)
    {
        $this->middleware(['verify.categories.count:casestudy'])->only(['create', 'store']);
        $this->indexPath = 'admin.casestudies.index';
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

        $data = $order == 'asc' ? Casestudy::with(['category','createdBy'])->Search($query)->get()->sortBy($orderBy) : Casestudy::with(['category','createdBy'])->Search($query)->get()->sortByDesc($orderBy);
       
        $casestudies = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.casestudies.data')->with('casestudies', $casestudies)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('casestudies',$casestudies);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $categories = Category::where('type', '=', 'casestudy')->orderBy('title','asc')->get();

        return view('admin.casestudies.create')->with(['categories' => $categories]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateCasestudiesRequest $request)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $image = $request->image->store('casestudies');
        $company_logo = $request->company_logo->store('casestudies/company_logo');

        $casestudies = Casestudy::create([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'content' => $request->content,
            'image'=> $image,
            'casestudy_date' => $request->casestudy_date,
            'casestudy_by' => $request->casestudy_by,
            'company' => $request->company,
            'company_url' => $request->company_url,
            'company_logo' => $company_logo,
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'slug' => $request->slug,
            'status' => $request->status ?: 0,
            'published_at' => $request->published_at,
            'created_by' => auth()->user()->id,
            'last_updated_by' => auth()->user()->id,
        ]);

        session()->flash('success', 'Casestudy added sucessfully.');

        return redirect(route($this->indexPath));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Casestudy $casestudy)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.casestudies.show')->with('casestudy', $casestudy);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Casestudy $casestudy)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.casestudies.create')->with(['casestudy' => $casestudy, 'categories' => Category::all()]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCasestudiesRequest $request, Casestudy $casestudy)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $data = $request->only([
            'category_id',
            'tags',
            'title', 
            'content', 
            'description', 
            'image',
            'casestudy_date',
            'casestudy_by',
            'company',
            'company_url',
            'company_logo', 
            'seo_title', 
            'seo_description', 
            'slug', 
            'status', 
            'published_at',  
            'last_updated_by',
        ]);

        if($request->hasFile('image')){
            $image = $request->image->store('casestudies');
            $casestudy->deleteCasestudyImage();
            $data['image'] = $image;
        }

        if($request->hasFile('company_logo')){
            $company_logo = $request->company_logo->store('casestudies/company_logo');
            $casestudy->deleteCasestudyCompanyLogo();
            $data['company_logo'] = $company_logo;
        }

        $data['status'] = $request->status ?: 0;

        $data['last_updated_by'] = auth()->user()->id;

        $casestudy->update($data);

        session()->flash('success', 'Casestudy updated sucessfully.');

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

        $casestudy = Casestudy::withTrashed()->where('id', $id)->firstOrFail();

        if($casestudy){
            if($casestudy->trashed()){
                $casestudy->deleteCasestudyImage();
                $casestudy->deleteCasestudyCompanyLogo();
                $casestudy->forceDelete();
            } else {
                $casestudy->delete();
            }
            session()->flash('success', 'Casestudy deleted sucessfully.');
        } else {
            session()->flash('error', 'Casestudy not found.');
        }
        
        return redirect(route($this->indexPath));
    }

    /**
     * Display a list of all articles
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
        
        $data = $order == 'asc' ? Casestudy::with(['category','createdBy'])->onlyTrashed()->Search($query)->get()->sortBy($orderBy) : Casestudy::with(['category','createdBy'])->onlyTrashed()->Search($query)->get()->sortByDesc($orderBy);

        $casestudies = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.casestudies.data')->with('casestudies', $casestudies)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('casestudies',$casestudies);
    }

    /**
     * Restore a article
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $casestudy = Casestudy::withTrashed()->where('id', $id)->firstOrFail();
        
        if($casestudy){

            $casestudy->restore();

            session()->flash('success', 'Casestudy restored sucessfully.');
        }
        return redirect()->back();
    }

    /**
     * Change status for a casestudy
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(Casestudy $casestudy, AdminToggleStatus $changeRecordStatus) 
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        
        $changeRecordStatus->toggleStatus($casestudy);
    }

    /**
     * export casestudy data
     *
     * @return csv file.
     */
    public function export() 
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return Excel::download(new CasestudiesExport, 'casestudies.csv');
    }

    /**
     * check casestudy slug from title
     *
     * @return response casestudy slug.
     */
    public function checkSlug(Request $request, Slug $slug)
    {
        $slug = $slug->createSlug(Casestudy::class, $request->title, $request->id);
        return response()->json(['slug' => $slug]);
    }
    
}
