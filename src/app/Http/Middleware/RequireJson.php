<?php
/**
 * RequireJson middleware
 * 2026/04/12 Mori Akiko
 * リクエストヘッダに Accept:application/json を加えるミドルウェア
 * APIリクエストに対して、常にJSON形式のレスポンスを返すために使用される
 */
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireJson
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // return $next($request);
        // リクエストヘッダに Accept:application/json を加える
        $request->headers->set('Accept', 'application/json');
        return $next($request);
    }
}
