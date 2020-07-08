<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

class VerifyIsAdmin
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

        if(!auth()->user()->isAdmin())
        {
            session()->flash("error", "Sorry you don't have  permission to view this page.");
            return redirect(route('home'));
        }
        return $next($request);
    }
}
