<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Brand extends Model
{   
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'brands';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $fillable = [
    	'title', 
    	'description', 
        'logo',
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

        static::deleting(function($brands) {
            foreach ($brands->products()->get() as $article) {
                $article->delete();
            }
        });
        static::restoring(function($brands) {
            $brands->products()->withTrashed()->where('deleted_at', '>=', $brands->deleted_at)->restore();
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
    * Define products relationship
    *
    */
    public function products() {
        return $this->hasMany('App\Models\Product')->withTrashed();
    }

    /**
    * Delete brand logo
    * @return void
    */
    public function deleteBrandLogo()
    {
        
        if (Storage::disk('local')->exists('public/'.$this->logo)) 
        {
            Storage::disk('local')->delete('public/'.$this->logo);
        }
        return;
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
                ->where('brands.title', 'like', '%'.$search.'%')
                ->orwhereHas('createdBy', 
                    function ($query) use ($search) {
                        $query->Where('users.name','like', '%'.$search.'%');
                    });

        }

        return;
        
    }
}
