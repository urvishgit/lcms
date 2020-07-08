<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TeamExport;

use App\Services\AdminModulePermissionCheck;
use App\Services\AdminToggleStatus;
use App\Services\AdminPagination;
use App\Services\Slug;

use App\Http\Requests\Team\CreateTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;

use App\Models\Team;
use App\Models\Category;

class TeamsController extends AdminController
{
    private $checkModulePermission;
    
    private $module = 'team';
    
    public function __construct(AdminModulePermissionCheck $permissionCheckService)
    {
        $this->middleware(['verify.categories.count:team'])->only(['create', 'store']);
        $this->indexPath = 'admin.team.index';
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

        $data = $order == 'asc' ? Team::with(['category','createdBy'])->Search($query)->get()->sortBy($orderBy) : Team::with(['category','createdBy'])->Search($query)->get()->sortByDesc($orderBy);
       
        $teamMembers = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.team.data')->with('teamMembers', $teamMembers)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('teamMembers',$teamMembers);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $categories = Category::where('type', '=', 'team')->orderBy('title','asc')->get();

        return view('admin.team.create')->with(['categories' => $categories]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateTeamRequest $request)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $image = $request->image->store('teams');

        $article = Team::create([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'content' => $request->content,
            'image'=> $image,
            'job_title' => $request->job_title,
            'email' => $request->email,
            'tel' => $request->tel,
            'mobile' => $request->mobile,
            'linkedin' => $request->linkedin,
            'address' => $request->address,
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'slug' => $request->slug,
            'status' => $request->status ?: 0,
            'published_at' => $request->published_at,
            'created_by' => auth()->user()->id,
            'last_updated_by' => auth()->user()->id,
        ]);

        session()->flash('success', 'Team Member added sucessfully.');

        return redirect(route($this->indexPath));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Team $team)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.team.show')->with('team', $team);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Team $team)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.team.create')->with(['team' => $team, 'categories' => Category::all()]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $data = $request->only([
            'category_id',
            'tags',
            'title', 
            'content', 
            'description', 
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
            'last_updated_by',
        ]);

        if($request->hasFile('image')){
            $image = $request->image->store('teams');
            $team->deleteTeamMemberImage();
            $data['image'] = $image;
        }

        $data['status'] = $request->status ?: 0;

        $data['last_updated_by'] = auth()->user()->id;

        $team->update($data);


        session()->flash('success', 'Team updated sucessfully.');

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

        $team = Team::withTrashed()->where('id', $id)->firstOrFail();

        if($team){
            if($team->trashed()){
                $team->deleteTeamMemberImage();
                $team->tags()->detach();
                $team->forceDelete();
            } else {
                $team->delete();
            }
            session()->flash('success', 'Team member deleted sucessfully.');
        } else {
            session()->flash('error', 'Team member not found.');
        }
        
        return redirect(route($this->indexPath));
    }

    /**
     * Display a list of all team members
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
        
        $data = $order == 'asc' ? Team::with(['category','createdBy'])->onlyTrashed()->Search($query)->get()->sortBy($orderBy) : Team::with(['category','createdBy'])->onlyTrashed()->Search($query)->get()->sortByDesc($orderBy);

        $teamMembers = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.team.data')->with('teamMembers', $teamMembers)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('teamMembers',$teamMembers);
    }

    /**
     * Restore a team member
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $team = Team::withTrashed()->where('id', $id)->firstOrFail();
        
        if($team){

            $team->restore();

            session()->flash('success', 'Team member restored sucessfully.');
        }
        return redirect()->back();
    }

    /**
     * Change status for a team member
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(Team $team, AdminToggleStatus $changeRecordStatus) 
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        
        $changeRecordStatus->toggleStatus($team);
    }

    /**
     * export team data
     *
     * @return csv file.
     */
    public function export() 
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return Excel::download(new TeamExport, 'team.csv');
    }

    /**
     * check team slug from title
     *
     * @return response team slug.
     */
    public function checkSlug(Request $request, Slug $slug)
    {
        $slug = $slug->createSlug(Team::class, $request->title, $request->id);
        return response()->json(['slug' => $slug]);
    }
    
}
