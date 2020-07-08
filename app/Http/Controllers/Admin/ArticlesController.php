<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArticlesExport;

use App\Services\AdminModulePermissionCheck;
use App\Services\AdminToggleStatus;
use App\Services\AdminPagination;
use App\Services\Slug;

use App\Http\Requests\Article\CreateArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;

class ArticlesController extends AdminController
{
    private $checkModulePermission;
    
    private $module = 'article';
    
    public function __construct(AdminModulePermissionCheck $permissionCheckService)
    {
        $this->middleware(['verify.categories.count:article', 'verify.tags.count'])->only(['create', 'store']);
        $this->indexPath = 'admin.articles.index';
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

        $data = $order == 'asc' ? Article::with(['category','createdBy'])->Search($query)->get()->sortBy($orderBy) : Article::with(['category','createdBy'])->Search($query)->get()->sortByDesc($orderBy);
       
        $articles = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.articles.data')->with('articles', $articles)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('articles',$articles);
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

        return view('admin.articles.create')->with(['categories' => $categories, 'tags' => Tag::all()]);
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

        $image = $request->image->store('articles');

        $article = Article::create([
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

        session()->flash('success', 'Article added sucessfully.');

        return redirect(route($this->indexPath));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Article $article)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.articles.show')->with('article', $article);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Article $article)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        return view('admin.articles.create')->with(['article' => $article, 'categories' => Category::all(), 'tags' => Tag::all()]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateArticleRequest $request, Article $article)
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
            $image = $request->image->store('articles');
            $article->deleteArticleImage();
            $data['image'] = $image;
        }

        $data['status'] = $request->status ?: 0;

        $data['last_updated_by'] = auth()->user()->id;

        $article->update($data);

        if($request->tags) {
            $article->tags()->sync($request->tags);
        }

        session()->flash('success', 'Article updated sucessfully.');

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

        $article = Article::withTrashed()->where('id', $id)->firstOrFail();

        if($article){
            if($article->trashed()){
                $article->deleteArticleImage();
                $article->tags()->detach();
                $article->forceDelete();
            } else {
                $article->delete();
            }
            session()->flash('success', 'Article deleted sucessfully.');
        } else {
            session()->flash('error', 'Article not found.');
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
        
        $data = $order == 'asc' ? Article::with(['category','createdBy'])->onlyTrashed()->Search($query)->get()->sortBy($orderBy) : Article::with(['category','createdBy'])->onlyTrashed()->Search($query)->get()->sortByDesc($orderBy);

        $articles = $paginationService->pagination($data, route($this->indexPath), $pageNo);

        if($request->ajax())
        {

            $returnHTML = view('admin.articles.data')->with('articles', $articles)->render();
            
            return response()->json(array('success' => true, 'html'=>$returnHTML));
        }

        return view($this->indexPath)->with('articles',$articles);
    }

    /**
     * Restore a article
     *
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $this->checkModulePermission->checkModulePermission($this->module);

        $article = Article::withTrashed()->where('id', $id)->firstOrFail();
        
        if($article){

            $article->restore();

            session()->flash('success', 'Article restored sucessfully.');
        }
        return redirect()->back();
    }

    /**
     * Change status for a article
     *
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(Article $article, AdminToggleStatus $changeRecordStatus) 
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

        return Excel::download(new ArticlesExport, 'articles.csv');
    }

    /**
     * check article slug from title
     *
     * @return response article slug.
     */
    public function checkSlug(Request $request, Slug $slug)
    {
        $slug = $slug->createSlug(Article::class, $request->title, $request->id);
        return response()->json(['slug' => $slug]);
    }
    
}
