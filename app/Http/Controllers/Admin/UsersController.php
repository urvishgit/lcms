<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Symfony\Component\HttpFoundation\Response;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;

use App\Services\AdminModulePermissionCheck;
use App\Services\AdminToggleStatus;
use App\Services\AdminPagination;
use App\Services\Slug;

use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserProfileRequest;

use App\Models\User;
use App\Models\Module;

class UsersController extends AdminController
{
    
    private $checkModulePermission;
    
    private $module = 'user';

    public function __construct(AdminModulePermissionCheck $permissionCheckService)
    {

        $this->indexPath = 'admin.users.index';
        $this->checkModulePermission = $permissionCheckService;
    }

    public function index(Request $request, AdminPagination $paginationService) 
    {

        $this->checkModulePermission->checkModulePermission($this->module);
        
        $query = $request->query('q');
        $orderBy = $request->query('orderBy') ?: 'id';
        $order = $request->query('order') ?: 'desc';
        $pageNo = $request->query('pageNo') ?:1;

        $data = $order == 'asc' ? User::Search($query)->get()->sortBy($orderBy) : User::Search($query)->get()->sortByDesc($orderBy);
       
        $users = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax()) {

            $returnHTML = view('admin.users.data')->with('users', $users)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('users',$users);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.users.create')->with(['editProfile' => false, 'roles' => User::getRoles(), 'modules' => auth()->user()->modules]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateUserRequest $request)
    {
        $this->checkModulePermission->checkModulePermission($this->module);
    	
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'about' => $request->about,
            'role'=> $request->role,
            'status' => $request->status,
        ]);

        if($request->modules) {
            $user->modules()->sync($request->modules);
        }

        session()->flash('success', 'User added sucessfully.');

        return redirect(route('admin.users.index'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        if(auth()->user()->isadmin() != 'administrator' && $user->role == 'administrator')
        {
            session()->flash("error", "Sorry you don't have  permission to edit administrator.");

            return redirect(route('admin.home'));
        }
    	return view('admin.users.create')->with(['user' => $user, 'editProfile' => false, 'roles' => User::getRoles(), 'modules' => auth()->user()->modules]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
    	$this->checkModulePermission->checkModulePermission($this->module);

    	if(!auth()->user()->isadmin()) {

    		session()->flash("error", "Sorry you don't have  permission to view this page.");

    		return redirect(route('admin.home'));
    	}

        $data = $request->only(['name', 'email', 'about', 'role', 'status']);

        $user->update($data);

        if( $request->modules ) {
            $user->modules()->sync($request->modules);
        }
        
        session()->flash('success', 'User updated sucessfully.');

        return redirect(route('admin.users.index'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editProfile()
    {
        return view('admin.users.create')->with(['user' => auth()->user(), 'editProfile' => true, 'roles' => User::getRoles(), 'modules' => auth()->user()->modules]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(UpdateUserProfileRequest $request)
    {

    	$user = auth()->user();

        $data = $request->only(['name', 'email', 'about', 'role', 'status']);

        $user->update($data);

        if($request->modules) {
            $user->modules()->sync($request->modules);
        }

        session()->flash('success', 'Your Profile updated sucessfully.');

        return redirect(route('admin.home'));
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

        $user = User::withTrashed()->where('id', $id)->firstOrFail();

        if($user) {

            if($user->trashed()) {
                
                $user->categories()->delete();
                
                $user->tags();

                foreach ($user->articles()->get() as $article) {
                    $article->deleteArticleImage();
                    $article->tags()->detach();
                }

                $user->articles();
                
                $user->modules()->detach();
                
                $user->forceDelete();

            } else {
                $user->delete();
            }
            session()->flash('success', 'User and related all the information deleted sucessfully.');
        } else {
            session()->flash('error', 'User not found.');
        }

        return redirect(route($this->indexPath));
    }

    /**
     * Display a list of all trashed users
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
        
        $data = $order == 'asc' ? User::onlyTrashed()->Search($query)->get()->sortBy($orderBy) : User::onlyTrashed()->Search($query)->get()->sortByDesc($orderBy);

        $users = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.users.data')->with('users', $users)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('users',$users);
    }

    /**
     * Restore a user
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $user = User::withTrashed()->where('id', $id)->firstOrFail();
        
        if($user){

            $user->restore();

            session()->flash('success', 'User restored sucessfully.');
        }
        return redirect()->back();
    }

    /**
     * Change status for a user
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(User $user, AdminToggleStatus $changeRecordStatus) 
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        
        $changeRecordStatus->toggleStatus($user);
    }

    /**
     * export users data
     *
     * @return csv file.
     */
    public function export() 
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return Excel::download(new UsersExport, 'users.csv');
    }
}
