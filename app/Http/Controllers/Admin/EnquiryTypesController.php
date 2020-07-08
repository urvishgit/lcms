<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

//use Maatwebsite\Excel\Facades\Excel;
//use App\Exports\EnquriyTypesExport;

use App\Services\AdminModulePermissionCheck;
use App\Services\AdminToggleStatus;
use App\Services\AdminPagination;
use App\Services\Slug;

class EnquiryTypesController extends AdminController
{
    private $checkModulePermission;
    
    private $module = 'enquiries';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AdminModulePermissionCheck $permissionCheckService)
    {
        $this->middleware('auth');
        $this->indexPath = 'admin.enquiries.index';
        $this->checkModulePermission = $permissionCheckService;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
