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

            ), 412);
        }
        // if (!isset($request->header()['x-forwarded-for']) || $request->header()['x-forwarded-for'] != '2403:6200:8810:9836:91ee:e555:7878:5a61') {
        //     return response()->json(array(
        //         'success' => false,
        //         'message' => 'incoming ip.',

        //     ), 511);
        // }
        // if (!isset($request->header()['Content-Length']) || $request->header()['Content-Length'] != 90) {
        //     return response()->json(array(
        //         'success' => false,
        //         'message' => 'content incorect',

        //     ), 411);
        // }
        return $next($request);
    }
}
