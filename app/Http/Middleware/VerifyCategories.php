<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Category;

class VerifyCategories
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $type='article')
    {
        if(Category::where('type', '=', $type)->count() == 0) {
            session()->flash('error', 'Please create '.$type.' category first.');
            return redirect(route('admin.categories.create'));
        }
        return $next($request);
    }
}
