<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\Storage;

class Article extends Model
{
	use SoftDeletes;
	
    protected $table = 'articles';
    
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'category_id', 
        'title', 
        'description', 
        'content', 
        'image', 
        'seo_title', 
        'seo_description', 
        'slug', 
        'status', 
        'published_at',  
        'created_by', 
        'last_updated_by',
    ];

    /**
    * Define Category relationship
    *
    */
    public function category() 
    {
	    return $this->belongsTo('App\Models\Category')->orderBy('categories.title')->withTrashed();
	}

    /**
    * Define tag relationship
    *
    */
    public function tags() 
    {
        return $this->belongsToMany('App\Models\Tag')->withTimestamps()->withTrashed();
    }

    /**
    * Define created by relationship
    *
    */
    public function createdBy() 
    {
        return $this->belongsTo('App\Models\User', 'created_by')->orderBy('users.name')->withTrashed();
    }

    /**
    * Define last updated by relationship
    *
    */
    public function lastUpdatedBy() 
    {
        return $this->belongsTo('App\Models\User', 'last_updated_by')->withTrashed();
    }

	/**
	* Delete article image
	* @return void
	*/
	public function deleteArticleImage()
    {
        
        if (Storage::disk('local')->exists('public/'.$this->image)) 
        {
            Storage::disk('local')->delete('public/'.$this->image);
        }
        return;
   }

   /**
    * Check article has Tag
    * @return tag array
    */
    public function hasTag($tagId) 
    {
        return in_array($tagId, $this->tags->pluck('id')->toArray());
    }


   /**
    * set article search query
    * @return article query
    */
    public function scopeSearch(Builder $query, ?string $search)
    {    

        if( $search )
        {
            return $query
                ->where('articles.title', 'like', '%'.$search.'%')
                ->orWhere('articles.description', 'like', '%'.$search.'%')
                ->orWhere('articles.content', 'like', '%'.$search.'%')
                ->orWhere('articles.seo_title', 'like', '%'.$search.'%')
                ->orWhere('articles.seo_description', 'like', '%'.$search.'%')
                ->orwhereHas('category', 
                    function ($query) use ($search) {
                        $query->Where('categories.title','like', '%'.$search.'%');
                    })
                ->orwhereHas('createdBy', 
                    function ($query) use ($search) {
                        $query->Where('users.name','like', '%'.$search.'%');
                    });

        }

        return;
        
    }
}