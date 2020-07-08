<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;

use App\Services\AdminModulePermissionCheck;
use App\Services\AdminToggleStatus;
use App\Services\AdminPagination;
use App\Services\Slug;

use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;

use App\Models\Product;
use App\Models\Category;

class ProductsController extends AdminController
{
    private $checkModulePermission;
    
    private $module = 'product';
    
    public function __construct(AdminModulePermissionCheck $permissionCheckService)
    {
        $this->middleware(['verify.categories.count:product'])->only(['create', 'store']);
        $this->indexPath = 'admin.products.index';
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

        $data = $order == 'asc' ? Product::with(['category','createdBy'])->Search($query)->get()->sortBy($orderBy) : Product::with(['category','createdBy'])->Search($query)->get()->sortByDesc($orderBy);
       
        $products = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.products.data')->with('products', $products)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('products',$products);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $categories = Category::where('type', '=', 'article')->orderBy('title','asc')->get();

        return view('admin.products.create')->with(['categories' => $categories, 'tags' => Tag::all()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateArticleRequest $request)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $image = $request->image->store('products');

        $article = Product::create([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'content' => $request->content,
            'image'=> $image,
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'slug' => $request->slug,
            'status' => $request->status ?: 0,
            'published_at' => $request->published_at,
            'created_by' => auth()->user()->id,
            'last_updated_by' => auth()->user()->id,
        ]);

        if($request->tags) {
            $article->tags()->attach($request->tags);
        }

        session()->flash('success', 'Product added sucessfully.');

        return redirect(route($this->indexPath));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $article)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.products.show')->with('article', $article);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $article)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.products.create')->with(['article' => $article, 'categories' => Category::all(), 'tags' => Tag::all()]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateArticleRequest $request, Product $article)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $data = $request->only([
            'category_id',
            'tags',
            'title', 
            'content', 
            'description', 
            'image', 
            'seo_title', 
            'seo_description', 
            'slug', 
            'status', 
            'published_at',  
            'last_updated_by',
        ]);

        if($request->hasFile('image')){
            $image = $request->image->store('products');
            $article->deleteArticleImage();
            $data['image'] = $image;
        }

        $data['status'] = $request->status ?: 0;

        $data['last_updated_by'] = auth()->user()->id;

        $article->update($data);

        if($request->tags) {
            $article->tags()->sync($request->tags);
        }

        session()->flash('success', 'Product updated sucessfully.');

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

        $article = Product::withTrashed()->where('id', $id)->firstOrFail();

        if($article){
            if($article->trashed()){
                $article->deleteArticleImage();
                $article->tags()->detach();
                $article->forceDelete();
            } else {
                $article->delete();
            }
            session()->flash('success', 'Product deleted sucessfully.');
        } else {
            session()->flash('error', 'Product not found.');
        }
        
        return redirect(route($this->indexPath));
    }

    /**
     * Display a list of all products
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
        
        $data = $order == 'asc' ? Product::with(['category','createdBy'])->onlyTrashed()->Search($query)->get()->sortBy($orderBy) : Product::with(['category','createdBy'])->onlyTrashed()->Search($query)->get()->sortByDesc($orderBy);

        $products = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.products.data')->with('products', $products)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('products',$products);
    }

    /**
     * Restore a article
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $article = Product::withTrashed()->where('id', $id)->firstOrFail();
        
        if($article){

            $article->restore();

            session()->flash('success', 'Product restored sucessfully.');
        }
        return redirect()->back();
    }

    /**
     * Change status for a article
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(Product $article, AdminToggleStatus $changeRecordStatus) 
    {
        $this->checkModulePermission->checkModulePermission($this->module);
        
        $changeRecordStatus->toggleStatus($article);
    }

    /**
     * export article data
     *
     * @return csv file.
     */
    public function export() 
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return Excel::download(new ArticlesExport, 'products.csv');
    }

    /**
     * check article slug from title
     *
     * @return response article slug.
     */
    public function checkSlug(Request $request, Slug $slug)
    {
        $slug = $slug->createSlug(Product::class, $request->title, $request->id);
        return response()->json(['slug' => $slug]);
    }
    
}
