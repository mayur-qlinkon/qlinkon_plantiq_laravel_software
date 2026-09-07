<?php

namespace App\Services\Tenancy;

use App\Models\Company;
use Illuminate\Http\Request;

/**
 * Single source of truth for tenant (Company) resolution.
 *
 * Priority (specific → general):
 *   1. Custom domain  → companies.domain
 *   2. Subdomain      → companies.subdomain (*.central_domain)
 *   3. Slug           → companies.slug (route parameter)
 *
 * This is company-level only. Store-level is an internal admin concept
 * handled separately via active_store() / session.
 */
class TenantResolver
{
    public const MODE_SLUG          = 'slug';
    public const MODE_SUBDOMAIN     = 'subdomain';
    public const MODE_CUSTOM_DOMAIN = 'custom_domain';

    /**
     * Resolve the tenant Company for the current request.
     *
     * @return array{company: Company|null, mode: string}
     */
    public function resolve(Request $request, ?string $routeSlug = null): array
    {
        $host          = strtolower($request->getHost());
        $centralDomain = strtolower(config('app.central_domain', ''));

        // ── MODE 1: CUSTOM DOMAIN ──────────────────────────────────────
        // Host is not our central domain (or any subdomain of it).
        if ($centralDomain && ! $this->isCentralHost($host, $centralDomain)) {
            $result = $this->resolveByCustomDomain($host);
            if ($result['company']) {
                return $result;
            }
            // Smart fallback: host didn't match any registered custom domain.
            // If the URL still carries a slug, honor it — protects local dev
            // on 127.0.0.1 / localhost, unrelated hosts hitting our IP,
            // and any DNS-misconfiguration edge case.
        }

        // ── MODE 2: SUBDOMAIN ──────────────────────────────────────────
        // Host is under central domain — check if it has a tenant subdomain label.
        if ($centralDomain) {
            $label = $this->extractSubdomain($host, $centralDomain);

            if ($label !== null && ! $this->isReservedSubdomain($label)) {
                $result = $this->resolveBySubdomain($label);
                if ($result['company']) {
                    return $result;
                }
                // No match → fall through to slug (backward-compat)
            }
        }

        // ── MODE 3: SLUG ───────────────────────────────────────────────
        return $this->resolveBySlug($routeSlug);
    }

    // ──────────────────────────────────────────────────────────────────
    //  RESOLUTION STRATEGIES
    // ──────────────────────────────────────────────────────────────────

    protected function resolveByCustomDomain(string $host): array
    {
        $company = Company::where('domain', $host)
            ->where('is_active', true)
            ->first();

        return [
            'company' => $company,
            'mode'    => self::MODE_CUSTOM_DOMAIN,
        ];
    }

    protected function resolveBySubdomain(string $label): array
    {
        $company = Company::where('subdomain', $label)
            ->where('is_active', true)
            ->first();

        return [
            'company' => $company,
            'mode'    => self::MODE_SUBDOMAIN,
        ];
    }

    protected function resolveBySlug(?string $slug): array
    {
        $company = $slug
            ? Company::where('slug', $slug)
                ->where('is_active', true)
                ->first()
            : null;

        return [
            'company' => $company,
            'mode'    => self::MODE_SLUG,
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    //  HOST PARSING HELPERS
    // ──────────────────────────────────────────────────────────────────

    /** Is this host our apex or any subdomain of our central domain? */
    protected function isCentralHost(string $host, string $central): bool
    {
        return $host === $central
            || str_ends_with($host, '.' . $central);
    }

    /**
     * Extract subdomain label.
     * acme.yourapp.com  → "acme"
     * yourapp.com       → null  (apex, no subdomain)
     * www.yourapp.com   → "www" (reserved, caller skips)
     */
    protected function extractSubdomain(string $host, string $central): ?string
    {
        if ($host === $central) {
            return null;
        }

        $suffix = '.' . $central;
        if (str_ends_with($host, $suffix)) {
            return substr($host, 0, -strlen($suffix)) ?: null;
        }

        return null;
    }

    /** Subdomains reserved for platform use — never assigned to a tenant. */
    protected function isReservedSubdomain(string $label): bool
    {
        // APP_SUBDOMAIN = the subdomain your own app runs on (e.g. "acme" for acme.viewlink.in)
        $appSubdomain = config('app.subdomain'); // set APP_SUBDOMAIN in .env

        $reserved = array_filter(['www', 'app', 'api', 'admin', 'mail', 'cdn',
            'static', 'assets', 'blog', 'help', 'support',
            'status', 'dashboard', $appSubdomain]);

        return in_array($label, $reserved, true);
    }
}