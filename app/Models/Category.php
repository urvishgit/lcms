<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{   
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $fillable = [
    	'type', 
    	'title', 
    	'description', 
    	'slug', 
    	'status', 
    	'created_by', 
    	'last_updated_by',
    ];

    /**
    * Override parent boot and Call deleting event
    *
    * @return void
    */
    protected static function boot() 
    {
        parent::boot();

        static::deleting(function($categories) {
            foreach ($categories->articles()->get() as $article) {
                $article->delete();
            }
        });
        static::restoring(function($categories) {
            $categories->articles()->withTrashed()->where('deleted_at', '>=', $categories->deleted_at)->restore();
        });        

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
    * Define articles relationship
    *
    */
    public function articles() {
        return $this->hasMany('App\Models\Article')->withTrashed();
    }

    /**
    * set category search query
    * @return category query
    */
    public function scopeSearch(Builder $query, ?string $search)
    {    

        if( $search )
        {
            return $query
                ->where('categories.title', 'like', '%'.$search.'%')
                ->orwhereHas('createdBy', 
                    function ($query) use ($search) {
                        $query->Where('users.name','like', '%'.$search.'%');
                    });

        }

        return;
        
    }
}
