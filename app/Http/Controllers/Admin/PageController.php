<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageTemplate;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\PageContentSanitizer;
use App\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PageController extends Controller
{
    public function __construct(
        protected PageService $pageService,
        protected PageContentSanitizer $sanitizer,
    ) {}

    // ════════════════════════════════════════════════════
    //  INDEX
    // ════════════════════════════════════════════════════

    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $filters = $request->only(['search', 'type', 'is_published', 'per_page']);

        $pages = $this->pageService->getList($companyId, $filters);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $pages]);
        }

        return view('admin.storefront-sections.pages.index', [
            'companySlug' => $user->company->slug ?? 'default',
            'pages' => $pages,
            'filters' => $filters,
            'types' => Page::TYPES,
        ]);
    }

    // ════════════════════════════════════════════════════
    //  CREATE & STORE
    // ════════════════════════════════════════════════════

    public function create(): View
    {
        return view('admin.storefront-sections.pages.create', [
            'templates' => PageTemplate::selectable(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $companyId = Auth::user()->company_id;
        $validated = $request->validate($this->rules($request));
        $validated = $this->prepare($validated);

        try {
            $page = $this->pageService->create($validated, $companyId);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Page created successfully.',
                    'redirect' => route('admin.pages.edit', $page->id),
                ]);
            }

            return redirect()->route('admin.pages.index')
                ->with('success', 'Page created successfully.');

        } catch (Throwable $e) {
            return $this->handleError($request, 'Failed to create page.', $e);
        }
    }

    // ════════════════════════════════════════════════════
    //  EDIT & UPDATE
    // ════════════════════════════════════════════════════

    public function edit(Page $page): View
    {
        $this->authorizePage($page);

        return view('admin.storefront-sections.pages.edit', [
            'page' => $page,
            'templates' => PageTemplate::selectable(),
        ]);
    }

    public function update(Request $request, Page $page): JsonResponse|RedirectResponse
    {
        $this->authorizePage($page);
        $validated = $request->validate($this->rules($request, $page));
        $validated = $this->prepare($validated);

        try {
            $updatedPage = $this->pageService->update($page, $validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Page updated successfully.',
                    'data' => $updatedPage,
                ]);
            }

            return redirect()->route('admin.pages.index')
                ->with('success', 'Page updated successfully.');

        } catch (Throwable $e) {
            return $this->handleError($request, 'Failed to update page.', $e);
        }
    }

    // ════════════════════════════════════════════════════
    //  AJAX ACTIONS (Toggle Publish & Delete)
    // ════════════════════════════════════════════════════

    public function togglePublish(Page $page): JsonResponse
    {
        $this->authorizePage($page);

        try {
            $isPublished = $this->pageService->togglePublish($page);

            return response()->json([
                'success' => true,
                'is_published' => $isPublished,
                'message' => $isPublished ? 'Page is now live.' : 'Page moved to drafts.',
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to toggle status.'], 500);
        }
    }

    public function destroy(Page $page): JsonResponse
    {
        $this->authorizePage($page);

        try {
            $this->pageService->delete($page);

            return response()->json([
                'success' => true,
                'message' => "Page '{$page->title}' deleted successfully.",
            ]);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete page.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Strict multi-tenant security check.
     */
    private function authorizePage(Page $page): void
    {
        if ($page->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this page.');
        }
    }

    /**
     * Centralized validation rules.
     */
    /**
     * Base rules plus whatever the chosen template declares.
     *
     * The field rules come from the template itself, so a template that gains
     * a field cannot be saved without that field also being validated.
     *
     * 'content' is gone: tenants no longer author HTML. Custom is rejected
     * here too — it is a marker for pre-template rows, not something anyone
     * may select.
     */
    private function rules(Request $request, ?Page $page = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'template' => [
                'required',
                Rule::in(array_map(fn (PageTemplate $t) => $t->value, PageTemplate::selectable())),
            ],
            'data' => ['nullable', 'array'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:1000'],
            'is_published' => ['boolean'],
        ];

        $template = PageTemplate::tryFrom((string) $request->input('template'));

        return $template
            ? array_merge($rules, $template->validationRules())
            : $rules;
    }

    /**
     * Clean the rich-text fields and derive the page type.
     *
     * Sanitising happens here, on the server, on every save. The editor's
     * toolbar only limits what is convenient to type — the form can be posted
     * to directly with any markup at all.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepare(array $validated): array
    {
        $template = PageTemplate::from($validated['template']);

        $validated['data'] = $this->sanitizer->clean($template, $validated['data'] ?? []);
        $validated['type'] = $template->pageType();

        return $validated;
    }

    /**
     * Graceful error handler for API and Standard requests.
     */
    private function handleError(Request $request, string $message, Throwable $e): JsonResponse|RedirectResponse
    {
        // Exception is already logged in the PageService, no need to double-log here unless it's a controller-specific error.
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 500);
        }

        return back()->withInput()->with('error', $message);
    }
}
