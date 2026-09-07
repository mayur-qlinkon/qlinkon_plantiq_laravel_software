<?php

use App\Http\Controllers\PwaManifestController;
use Illuminate\Support\Facades\Route;

/*
|==========================================================================
| PWA MANIFEST — Tenant-aware, generated per request.
|
| IdentifyTenant is prepended to the global 'web' middleware group (see
| bootstrap/app.php), so tenant()/tenant_mode() are already resolved by
| the time PwaManifestController runs — no extra tenant lookup here.
|
| Loaded BEFORE custom_domain.php / admin.php / storefront.php so
| `/{slug}/manifest.json` can never be shadowed by a storefront catch-all
| sharing the same slug prefix.
|==========================================================================
*/

// 1. SLUG-BASED TENANT — smartbiz.in/acme/manifest.json
Route::get('/{slug}/manifest.json', [PwaManifestController::class, 'index'])
    ->name('pwa.manifest.slug');

// 2. HOST-BASED — subdomain, custom domain, or apex (platform)
//    acme.smartbiz.in/manifest.json | clientdomain.com/manifest.json | smartbiz.in/manifest.json
Route::get('/manifest.json', [PwaManifestController::class, 'index'])
    ->name('pwa.manifest');