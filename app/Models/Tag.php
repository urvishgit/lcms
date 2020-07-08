<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Tag extends Model
{
  use SoftDeletes;

  protected $fillable = [
  	'title', 
  	'description', 
  	'slug', 
  	'status', 
  	'created_by', 
  	'last_updated_by',
  ];

  /**
  * The attributes that should be mutated to dates.
  *
  * @var array
  */
  protected $dates = ['deleted_at'];

  /**
  * Override parent boot and Call deleting event
  *
  * @return void
  */
  protected static function boot() 
  {
    parent::boot();

    static::deleted(function ($tags) {
      foreach ($tags->articles()->get() as $article) {
        $article->delete();
      }
    });
    static::restoring(function($tags) {
      $tags->articles()->withTrashed()->where('deleted_at', '>=', $tags->deleted_at)->restore();
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
    return $this->belongsToMany('App\Models\Article')->withTimestamps()->withTrashed();
	}

  /**
  * set tag search query
  * @return tag query
  */
  public function scopeSearch(Builder $query, ?string $search)
  {    

    if( $search )
    {
      return $query
        ->where('tags.title', 'like', '%'.$search.'%')
        ->orwhereHas('createdBy', 
          function ($query) use ($search) {
            $query->Where('users.name','like', '%'.$search.'%');
          });
    }
    return;
  }

}
