<?php

namespace App\Http\Middleware;

use App\Services\Auth\SessionContextBuilder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RebuildTenantSessionContext
{
    public function __construct(private SessionContextBuilder $builder)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $this->builder->isStale($user)) {
            $this->builder->build($user);
        }

        return $next($request);
    }
}