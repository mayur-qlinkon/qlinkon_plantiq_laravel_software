<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\PageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontPageController extends Controller
{
    public function __construct(
        protected PageService $pageService
    ) {}

    /**
     * Display a public CMS page.
     *
     * * @param string $slug The company slug from the URL (e.g., /my-store/...)
     * @param  string  $pageSlug  The page slug (e.g., /page/privacy-policy)
     */
    public function show(Request $request): View
    {
        // FIX: Extract exactly the pageSlug from the route, ignoring the tenant slug
        $pageSlug = $request->route('pageSlug');

        $company = tenant() ?? abort(404);

        // 2. Fetch the Page via Service (Ensures it is published)
        $page = $this->pageService->getPublicPage($pageSlug, $company->id);

        if (! $page) {
            abort(404, 'This page could not be found or is no longer available.');
        }

        // 3. Render the public view
        return view('storefront.page', compact('company', 'page'));
    }
}