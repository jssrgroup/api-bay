<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyIsValid
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
        if (!isset($request->header()['api-key']) || $request->header()['api-key'][0] !== 'WtqLoQbKJT3yhxwQLsIx4v5NDMa5pTC6') {
            return response()->json(array(
                'success' => false,
                'message' => 'api-key invalid.',

            ),412);
        }
        return $next($request);
    }
}
