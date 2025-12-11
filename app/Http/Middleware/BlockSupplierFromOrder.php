<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockSupplierFromOrder
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
        // لو المستخدم Supplier → امنعه
        if (auth()->user() && auth()->user()->role === 'supplier') {
            return response()->json([
                'message' => 'Suppliers are not allowed to create orders.'
            ], 403);
        }

        return $next($request);
    }
}
