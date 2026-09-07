<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PwaManifestController extends Controller
{
    public function index(): JsonResponse
    {
        $company = tenant();

        $manifest = $company
            ? $this->tenantManifest($company, tenant_mode())
            : $this->platformManifest();

        return response()
            ->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=600');
    }

    /**
     * scope/id are deliberately ROOT-RELATIVE paths — the spec resolves
     * them relative to the manifest's own URL, which is already the
     * tenant-correct URL (fetched from /acme/manifest.json or from the
     * tenant's own subdomain/custom-domain host). start_url is kept
     * absolute via tenant_url() so the value stored at install time is
     * unambiguous regardless of resolution context.
     */
    protected function tenantManifest(Company $company, ?string $mode): array
    {
        $scope = $mode === TenantResolver::MODE_SLUG
            ? '/' . $company->slug . '/'
            : '/';

        return [
            'id'               => $scope,
            'name'             => config('app.name'),
            'short_name'       => config('app.name'),
            'start_url'        => tenant_url('admin'),
            'scope'            => $scope,
            'display'          => 'standalone',
            'orientation'      => 'portrait-primary',
            'background_color' => '#ffffff',
            'theme_color'      => '#82cd47',
            'icons'            =>  $this->defaultIcons(),
        ];
    }

    protected function platformManifest(): array
    {
        return [
            'id'               => '/',
            'name'             => config('app.name'),
            'short_name'       => config('app.name'),
            'start_url'        => url('/admin/dashboard'),
            'scope'            => '/',
            'display'          => 'standalone',
            'orientation'      => 'portrait-primary',
            'background_color' => '#ffffff',
            'theme_color'      => '#82cd47',
            'icons'            => $this->defaultIcons(),
        ];
    }  

    /**
     * ACTION NEEDED: add real 192x192 and 512x512 PNGs at these paths.
     * Only apple-touch-icon (favicon.png) exists today — Chrome will not
     * consider the app installable without both of these sizes present.
     */
    protected function defaultIcons(): array
    {
        return [
            [
                'src' => asset('assets/pwa/icon-192.webp'),
                'sizes' => '192x192',
                'type' => 'image/webp',
                'purpose' => 'any',
            ],
            [
                'src' => asset('assets/pwa/icon-512.webp'),
                'sizes' => '512x512',
                'type' => 'image/webp',
                'purpose' => 'any',
            ],
            [
                'src' => asset('assets/pwa/icon-512-maskable.webp'),
                'sizes' => '512x512',
                'type' => 'image/webp',
                'purpose' => 'maskable',
            ],
        ];
    }
}