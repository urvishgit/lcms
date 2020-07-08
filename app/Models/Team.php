<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\Storage;

class Team extends Model
{
	use SoftDeletes;
	
    protected $table = 'team';
    
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'category_id', 
        'title', 
        'description', 
        'content', 
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
	* Delete article image
	* @return void
	*/
	public function deleteTeamMemberImage()
    {
        
        if (Storage::disk('local')->exists('public/team/'.$this->image)) 
        {
            Storage::disk('local')->delete('public/team/'.$this->image);
        }
        return;
   }

   /**
    * set team search query
    * @return team query
    */
    public function scopeSearch(Builder $query, ?string $search)
    {    

        if( $search )
        {
            return $query
                ->where('team.title', 'like', '%'.$search.'%')
                ->orWhere('team.description', 'like', '%'.$search.'%')
                ->orWhere('team.content', 'like', '%'.$search.'%')
                ->orWhere('team.job_title', 'like', '%'.$search.'%')
                ->orWhere('team.email', 'like', '%'.$search.'%')
                ->orWhere('team.tel', 'like', '%'.$search.'%')
                ->orWhere('team.mobile', 'like', '%'.$search.'%')
                ->orWhere('team.linkedin', 'like', '%'.$search.'%')
                ->orWhere('team.address', 'like', '%'.$search.'%')
                ->orWhere('team.seo_title', 'like', '%'.$search.'%')
                ->orWhere('team.seo_description', 'like', '%'.$search.'%')
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