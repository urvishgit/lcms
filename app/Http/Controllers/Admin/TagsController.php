<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TagsExport;

use App\Services\AdminModulePermissionCheck;
use App\Services\AdminPagination;
use App\Services\AdminToggleStatus;
use App\Services\Slug;

use App\Http\Requests\Tag\CreateTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;

use App\Models\Tag;


class TagsController extends AdminController
{
    private $checkModulePermission;

    
    private $module = 'tag';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct ( AdminModulePermissionCheck $permissionCheckService )
    {
        $this->middleware('auth');
        $this->indexPath = 'admin.tags.index';
        $this->checkModulePermission = $permissionCheckService;
    }

    public function index(Request $request, AdminPagination $paginationService)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $query = $request->query('q');
        $orderBy = $request->query('orderBy') ?: 'id';
        $order = $request->query('order') ?: 'desc';
        $pageNo = $request->query('pageNo') ?:1;

        $data = $order == 'asc' ? Tag::with(['createdBy'])->Search($query)->get()->sortBy($orderBy) : Tag::with(['createdBy'])->Search($query)->get()->sortByDesc($orderBy);
       
        $tags = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.tags.data')->with('tags', $tags)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('tags',$tags);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.tags.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateTagRequest $request)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        Tag::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?: 0,
            'slug' => $request->slug,
            'created_by' => auth()->user()->id,
            'last_updated_by' => auth()->user()->id,
        ]);

        session()->flash('success', 'Tags added sucessfully.');

        return redirect(route($this->indexPath));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Tag $tag)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.tags.show')->with('tag', $tag);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Tag $tag)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.tags.create')->with('tag', $tag);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $data = $request->only(['title', 'description' ,'status', 'slug', 'last_updated_by']);

        $data['status'] = $request->status ?: 0;

        $data['last_updated_by'] = auth()->user()->id;

        $tag->update($data);


        session()->flash('success', 'Tag updated sucessfully.');

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

        $tag = Tag::withTrashed()->where('id', $id)->firstOrFail();
        
        if($tag) {
            if($tag->trashed()) {
                $tag->articles()->detach();
                $tag->forceDelete();
            } else {
                $tag->delete();
            }
            session()->flash('success', 'Tag deleted sucessfully.');
        } else {
            session()->flash('error', 'Tag not found.');
        }
        return redirect(route($this->indexPath));
    }

    /**
     * Display a list of all trashed all tags
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
        
        $data = $order == 'asc' ? Tag::with(['createdBy'])->onlyTrashed()->Search($query)->get()->sortBy($orderBy) : Tag::with(['createdBy'])->onlyTrashed()->Search($query)->get()->sortByDesc($orderBy);

        $tags = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.tags.data')->with('tags', $tags)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->withTags($tags);
    }

    /**
     * Restore a category
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $tag = Tag::withTrashed()->where('id', $id)->firstOrFail();
        
        if($tag){

            $tag->restore();

            session()->flash('success', 'Tag restored sucessfully.');
        }
        return redirect()->back();
    }

    /**
     * Change status for a category
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(Tag $tag, AdminToggleStatus $changeRecordStatus) 
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        
        $changeRecordStatus->toggleStatus($tag);
    }

    /**
     * export tag data
     *
     * @return csv file.
     */
    public function export() 
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        
        return Excel::download(new TagsExport, 'tags.csv');
    }

    /**
     * check tag slug from title
     *
     * @return response tag slug.
     */
    public function checkSlug(Request $request, Slug $slug)
    {
        $slug = $slug->createSlug(Tag::class, $request->title, $request->id);
        return response()->json(['slug' => $slug]);
    }
}
