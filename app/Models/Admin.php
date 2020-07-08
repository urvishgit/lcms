<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Authenticatable
{
    use Notifiable;
    
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 
        'email', 
        'password',
        'role', 
        'about',
        'status', 
    ];

    protected $guarded = array('id', 'password');

    /**
     * The user roles list.
     *
     */
    protected $roles = [
        'admin',
        'user',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 
        'remember_token',
    ];

    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
    * Override parent boot and Call deleting event
    *
    * @return void
    */
    protected static function boot() 
    {
        parent::boot();

        static::deleting(function($admins) {
            foreach ($admins->categories()->get() as $category) {
                $category->delete();
            }
            foreach ($admins->tags()->get() as $tag) {
                $tag->delete();
            }
            foreach ($admins->articles()->get() as $article) {
                $article->delete();
            }
        });

        static::restoring(function($admins) {
            
            $admins->categories()->withTrashed()->where('deleted_at', '>=', $admins->deleted_at)->restore();
            
            $admins->tags()->withTrashed()->where('deleted_at', '>=', $admins->deleted_at)->restore();

            $admins->articles()->withTrashed()->where('deleted_at', '>=', $admins->deleted_at)->restore();
        });     
    }

    /**
    * Define modules relationship
    *
    */
    public function modules() {
        return $this->belongsToMany('App\Models\Module')->withTimestamps();
    }

    /**
    * Define categories relationship
    *
    */
    public function categories() {
        return $this->hasMany('App\Models\Category', 'created_by')->withTrashed();
    }

    /**
    * Define tags relationship
    *
    */
    public function tags() {
        return $this->hasMany('App\Models\Tag', 'created_by')->withTrashed();
    }

    /**
    * Define articles relationship
    *
    */
    public function articles() {
        return $this->hasMany('App\Models\Article', 'created_by')->withTrashed();
    }

    /**
    * Check user has Module
    * @return module array
    */
    public function hasModule($moduleId) 
    {
        return in_array($moduleId, $this->modules->pluck('id')->toArray());
    }

    /**
    * Check user has Module
    * @return module array
    */
    public function hasModuleAllow($slug) 
    {
        return in_array($slug, $this->modules->pluck('slug')->toArray());
    }

    /**
    * Get user available roels
    * @return roles array;
    */
    public function scopeGetRoles()
    {
        return $this->roles;
    }

    /**
    * check user is admin
    * @return boolen
    */
    public function isAdmin() 
    {
        $allowed_role = ['administrator','admin'];

        if(in_array($this->role, $allowed_role)){
            return $this->role;
        }        
    }

    
    /**
    * set user search query
    * @return user query
    */
    public function scopeSearch(Builder $query, ?string $search)
    {    

        if( $search )
        {
            return $query
                ->where('admins.name', 'like', '%'.$search.'%')
                ->orWhere('admins.email', 'like', '%'.$search.'%')
                ->orWhere('admins.role', 'like', '%'.$search.'%');
        }
        return $query;
    }
}
