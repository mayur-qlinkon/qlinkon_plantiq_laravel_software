<?php

namespace App\Http\Controllers\Storefront\Concerns;

use App\Models\Company;

/**
 * Resolves the active storefront tenant for a request.
 *
 * Extracted so that every public storefront endpoint resolves the tenant
 * identically. The three access paths — /{slug}/..., subdomain and custom
 * domain — must all land on the same company, and having this logic in one
 * place is what keeps a new endpoint from accidentally skipping a case.
 */
trait ResolvesStorefrontCompany
{
    protected function resolveStorefrontCompany(): Company
    {
        // Slug route param takes HIGHEST priority — when URL has /{slug}/...,
        // the slug's company is ALWAYS correct, regardless of what host resolved.
        $routeSlug = request()->route('slug');

        if ($routeSlug) {
            $company = Company::where('slug', $routeSlug)
                ->where('is_active', true)
                ->first();

            if ($company) {
                return $company;
            }
        }

        // No slug in route → host-based tenant (subdomain / custom domain).
        $company = tenant();

        if (! $company) {
            abort(404);
        }

        return $company;
    }
}