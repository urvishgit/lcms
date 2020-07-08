<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\Storage;

class Casestudy extends Model
{
	use SoftDeletes;
	
    protected $table = 'casestudies';
    
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'category_id', 
        'title', 
        'description', 
        'content', 
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
	* Delete casestudy image
	* @return void
	*/
	public function deleteCasestudyImage()
    {
        
        if (Storage::disk('local')->exists('public/'.$this->image)) 
        {
            Storage::disk('local')->delete('public/'.$this->image);
        }
        return;
   }

   /**
	* Delete casestudy logo
	* @return void
	*/
	public function deleteCasestudyCompanyLogo()
    {
        
        if (Storage::disk('local')->exists('public/'.$this->company_logo)) 
        {
            Storage::disk('local')->delete('public/'.$this->company_logo);
        }
        return;
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
                ->where('casestudies.title', 'like', '%'.$search.'%')
                ->orWhere('casestudies.description', 'like', '%'.$search.'%')
                ->orWhere('casestudies.content', 'like', '%'.$search.'%')
                ->orWhere('casestudies.casestudy_by', 'like', '%'.$search.'%')
                ->orWhere('casestudies.company', 'like', '%'.$search.'%')
                ->orWhere('casestudies.seo_title', 'like', '%'.$search.'%')
                ->orWhere('casestudies.seo_description', 'like', '%'.$search.'%')
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