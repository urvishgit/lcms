<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tag;

class VerifyTags
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(Tag::all()->count() == 0) {
            session()->flash('error', 'Please create tag first.');
            return redirect(route('admin.tags.create'));
        }
        return $next($request);
    }
}
