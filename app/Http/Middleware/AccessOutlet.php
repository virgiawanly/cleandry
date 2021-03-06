<?php

namespace App\Http\Middleware;

use App\Models\Outlet;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccessOutlet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $outlet = is_numeric($request->route('outlet')) ? Outlet::find($request->route('outlet')) : $request->route('outlet');
        if (!$outlet || Auth::user()->role !== 'admin' && Auth::user()->outlet_id !== $outlet->id) {
            return abort(404);
        }
        return $next($request);
    }
}
