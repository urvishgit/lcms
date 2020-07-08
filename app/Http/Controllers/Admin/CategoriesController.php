<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CategoriesExport;

use App\Services\AdminModulePermissionCheck;
use App\Services\AdminToggleStatus;
use App\Services\AdminPagination;
use App\Services\Slug;

use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;

use App\Models\Category;

class CategoriesController extends AdminController
{
    private $checkModulePermission;
    
    private $module = 'category';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AdminModulePermissionCheck $permissionCheckService)
    {
        $this->middleware('auth');
        $this->indexPath = 'admin.categories.index';
        $this->checkModulePermission = $permissionCheckService;
    }

    public function index(Request $request, AdminPagination $paginationService)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $query = $request->query('q');
        $orderBy = $request->query('orderBy') ?: 'id';
        $order = $request->query('order') ?: 'desc';
        $pageNo = $request->query('pageNo') ?:1;

        $data = $order == 'asc' ? Category::with(['createdBy'])->Search($query)->get()->sortBy($orderBy) : Category::with(['createdBy'])->Search($query)->get()->sortByDesc($orderBy);
       
        $categories = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.categories.data')->with('categories', $categories)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('categories',$categories);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        return view('admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateCategoryRequest $request)
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        Category::create([
            'title' => $request->title,
            'type' => $request->type,
            'description' => $request->description,
            'status' => $request->status ?: 0,
            'slug' => $request->slug,
            'created_by' => auth()->user()->id,
            'last_updated_by' => auth()->user()->id,
        ]);

        session()->flash('success', 'Category added sucessfully.');

        return redirect(route($this->indexPath));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        return view('admin.categories.show')->with('category', $category);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        return view('admin.categories.create')->with('category', $category);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $data = $request->only(['title', 'type', 'description' ,'status', 'slug', 'last_updated_by']);

        $data['status'] = $request->status ?: 0;

        $data['last_updated_by'] = auth()->user()->id;

        $category->update($data);

        session()->flash('success', 'Category updated sucessfully.');

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

        $category = Category::withTrashed()->where('id', $id)->firstOrFail();
        
        if($category) {
            if($category->trashed()) {
                $category->forceDelete();
            } else {
                $category->delete();
            }
            session()->flash('success', 'Category deleted sucessfully.');
        } else {
            session()->flash('error', 'Category not found.');
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
        
        $data = $order == 'asc' ? Category::with(['createdBy'])->onlyTrashed()->Search($query)->get()->sortBy($orderBy) : Category::with(['createdBy'])->onlyTrashed()->Search($query)->get()->sortByDesc($orderBy);

        $categories = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.categories.data')->with('categories', $categories)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->withCategories($categories);
    }

    /**
     * Restore a category
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $category = Category::withTrashed()->where('id', $id)->firstOrFail();
        
        if($category){

            $category->restore();

            session()->flash('success', 'Category restored sucessfully.');
        }
        return redirect()->back();
    }

    /**
     * Change status for a category
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(Category $category, AdminToggleStatus $changeRecordStatus) 
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        
        $changeRecordStatus->toggleStatus($category);
    }

    /**
     * export post data
     *
     * @return csv file.
     */
    public function export() 
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return Excel::download(new CategoriesExport, 'categories.csv');
    }
    
    /**
     * check post slug from title
     *
     * @return response post slug.
     */
    public function checkSlug(Request $request, Slug $slug)
    {
        $slug = $slug->createSlug(Category::class, $request->title, $request->id);
        return response()->json(['slug' => $slug]);
    }
}
