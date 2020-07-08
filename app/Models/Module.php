<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Module extends Model
{
    use SoftDeletes;

    protected $table = 'modules';
    
    protected $dates = ['deleted_at'];


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'display_name',
        'description', 
        'icon', 
        'route', 
        'trash', 
        'trash_route', 
        'order',
        'is_administrator_module',
        'slug', 
        'status', 
        'published_at',  
    ];

    /**
    * Define posts relationship
    *
    */
    public function users() {
        return $this->belongsToMany('App\Models\User')->withTimestamps()->withTrashed();
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
                ->where('modules.title', 'like', '%'.$search.'%')
                ->orWhere('modules.display_name', 'like', '%'.$search.'%')
                ->orWhere('modules.description', 'like', '%'.$search.'%')
                ->orWhere('modules.icon', 'like', '%'.$search.'%')
                ->orWhere('modules.route', 'like', '%'.$search.'%')
                ->orWhere('modules.trash_route', 'like', '%'.$search.'%');

        }

        return;
        
    }
}   
