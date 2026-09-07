<?php

namespace App\Services\Admin;

use App\Models\CrmLead;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\Challan;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\Production\DailyTask;
use App\Models\Project\Project;
use App\Models\Project\ProjectCharge;
use App\Models\Project\ProjectClientService;
use App\Models\Production\PlantBatch;
use App\Models\Production\ZoneAssignment;
use App\Models\Hrm\Attendance;
use App\Services\Admin\AiContextBuilder;
use App\Services\Admin\AiService;
use App\Services\Admin\Ai\PhpSmallTalkHandler;
use App\Services\Admin\Ai\PhpErpCommandRouter;
use App\Services\Admin\Ai\PhpResponseFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * AiChatbotService — Plantiq AI SaaS Operations Assistant
 *
 * Flow:
 *   User Question
 *     → [1] Intent Router  (AI classifies → JSON plan)
 *     → [2] Query Engine   (Pure Laravel Eloquent — NO raw SQL, NO writes)
 *     → [3] Answer Format  (AI converts data → human-friendly reply)
 *     → JSON Response
 *
 * No DB tables. No sessions. No memory. Every request is self-contained.
 * Tenantable trait on all models ensures company_id isolation automatically.
 */
class AiChatbotService
{
    // ── Hard limits ───────────────────────────────────────────────────────────
    // Max characters in a single user message
    private const MAX_MESSAGE_LENGTH = 600;

    // Max rows returned in any list query (prevents huge AI prompts)
    private const MAX_LIST_ROWS = 10;

    /**
     * Maps every AI module → its required plan module + permission slug.
     *
     * 'module'     → has_module() slug. null = no plan restriction.
     * 'permission' → has_permission() slug. null = no permission check.
     *
     * Slugs match exactly what is used in routes/admin.php middleware.
     */
    private const MODULE_PERMISSION_MAP = [
        'invoices'       => ['module' => 'invoicing',  'permission' => 'invoices.view'],
        'quotations'     => ['module' => 'invoicing',  'permission' => 'quotations.view'],
        'orders'         => ['module' => null,          'permission' => 'orders.view'],
        'purchases'      => ['module' => 'purchases',  'permission' => 'purchases.view'],
        'expenses'       => ['module' => 'expenses',   'permission' => 'expenses.view'],
        'crm_leads'      => ['module' => 'crm',        'permission' => 'crm_leads.view'],
        'hrm_attendance' => ['module' => 'hrm',        'permission' => null],
        'clients'        => ['module' => null,          'permission' => 'clients.view'],
        'products'       => ['module' => 'inventory',  'permission' => 'products.view'],
        'suppliers'      => ['module' => 'purchases',  'permission' => 'suppliers.view'],
        'payments'       => ['module' => 'invoicing',  'permission' => 'invoices.view'],
        'challans'       => ['module' => 'invoicing',  'permission' => 'challans.view'],
        'production'     => ['module' => 'production', 'permission' => 'production_plant_batches.view'],
        'projects'       => ['module' => 'projects',   'permission' => 'project_client_services.view'],
    ];

    /**
     * Per-module routeIntent documentation + examples.
     * Only modules enabled in the tenant's plan (MODULE_PERMISSION_MAP['module']
     * present in $ctx['modules']['enabled']) are included in the prompt.
     * This is the source of truth for routeIntent's dynamic system prompt.
     */
    private const AI_MODULE_LIBRARY = [
        'invoices' => [
            'doc' => "MODULE: invoices\n  actions: total, count, list, search\n  periods: today, this_week, this_month, this_year, all\n  filters: status (draft/sent/paid/unpaid/overdue/partial/cancelled), payment_status (paid/unpaid/partial/overdue)\n  store_aware: YES",
            'examples' => [
                '"Today\'s sales" → {"module":"invoices","action":"total","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
                '"Today\'s sales for Downtown Store" → {"module":"invoices","action":"total","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":{"store_id":1,"store_name":"Downtown Store"},"extra":{}}',
                '"Show pending invoices" → {"module":"invoices","action":"list","period":"all","status_filter":"unpaid","payment_status_filter":"unpaid","search":null,"store_filter":null,"extra":{}}',
            ],
        ],
        'orders' => [
            'doc' => "MODULE: orders\n  actions: total, count, list\n  periods: today, this_week, this_month, this_year, all\n  filters: status (inquiry/confirmed/processing/shipped/out_for_delivery/delivered/cancelled/refunded)\n  store_aware: YES",
            'examples' => [
                '"How many orders were received today?" → {"module":"orders","action":"count","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
                '"Show delivered orders this month for Central Store" → {"module":"orders","action":"count","period":"this_month","status_filter":"delivered","payment_status_filter":null,"search":null,"store_filter":{"store_id":2,"store_name":"Central Store"},"extra":{}}',
            ],
        ],
        'purchases' => [
            'doc' => "MODULE: purchases\n  actions: total, count, list\n  periods: today, this_week, this_month, this_year, all\n  filters: status (draft/received/cancelled), payment_status (paid/unpaid/partial)\n  store_aware: YES",
            'examples' => [
                '"Show draft purchases" → {"module":"purchases","action":"list","period":"all","status_filter":"draft","payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
                '"Total draft purchases amount" → {"module":"purchases","action":"total","period":"all","status_filter":"draft","payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
            ],
        ],
        'expenses' => [
            'doc' => "MODULE: expenses\n  actions: total, count\n  periods: today, this_week, this_month, this_year, all\n  store_aware: YES",
            'examples' => [
                '"What are the total expenses for this month?" → {"module":"expenses","action":"total","period":"this_month","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
            ],
        ],
        'quotations' => [
            'doc' => "MODULE: quotations\n  actions: count, list\n  periods: today, this_week, this_month, all\n  filters: status (draft/sent/accepted/rejected/expired)\n  store_aware: YES",
            'examples' => [
                '"Show recent quotations" → {"module":"quotations","action":"list","period":"this_month","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
            ],
        ],
        'payments' => [
            'doc' => "MODULE: payments\n  actions: total, count\n  periods: today, this_week, this_month, this_year, all\n  filters: status (completed/pending/bounced)\n  store_aware: YES",
            'examples' => [
                '"Show total payments received today" → {"module":"payments","action":"total","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
            ],
        ],
        'challans' => [
            'doc' => "MODULE: challans\n  actions: count, list\n  periods: today, this_week, this_month, all\n  filters: status (draft/dispatched/in_transit/delivered)\n  store_aware: YES",
            'examples' => [
                '"Show delivered challans" → {"module":"challans","action":"list","period":"this_month","status_filter":"delivered","payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
            ],
        ],
        'hrm_attendance' => [
            'doc' => "MODULE: hrm_attendance\n  actions: count, list\n  periods: today\n  filters: status (present/absent/late/half_day)\n  store_aware: YES",
            'examples' => [
                '"Who is absent today?" → {"module":"hrm_attendance","action":"list","period":"today","status_filter":"absent","payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
                '"How many employees are present today?" → {"module":"hrm_attendance","action":"count","period":"today","status_filter":"present","payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
            ],
        ],
        'crm_leads' => [
            'doc' => "MODULE: crm_leads\n  actions: count, list\n  periods: today, this_week, this_month, all\n  filters: is_converted (true/false), followup_pending (true), score_hot (true)\n  store_aware: NO — always set store_filter to null",
            'examples' => [
                '"How many CRM leads were added today?" → {"module":"crm_leads","action":"count","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
                '"Show hot leads" → {"module":"crm_leads","action":"count","period":"all","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"score_hot":true}}',
                '"Show pending follow-up leads" → {"module":"crm_leads","action":"count","period":"all","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"followup_pending":true}}',
            ],
        ],
        'clients' => [
            'doc' => "MODULE: clients\n  actions: count, list, search\n  filters: search (name or email keyword)\n  store_aware: NO — always set store_filter to null",
            'examples' => [
                '"How many clients do we have?" → {"module":"clients","action":"count","period":null,"status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
            ],
        ],
        'products' => [
            'doc' => "MODULE: products\n  actions: count, list, search\n  filters: search (product name keyword)\n  store_aware: YES — when user asks about stock for a specific store/branch, populate store_filter; for general product listing set store_filter to null",
            'examples' => [
                '"Show low stock products" → {"module":"products","action":"list","period":null,"status_filter":"low_stock","payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
                '"Search product iPhone" → {"module":"products","action":"search","period":null,"status_filter":null,"payment_status_filter":null,"search":"iPhone","store_filter":null,"extra":{}}',
            ],
        ],
        'projects' => [
            'doc' => "MODULE: projects\n  Client work and recurring services (hosting, AMC, retainers). A client service's current_period_end IS its renewal date, and its charges are the money owed.\n  actions: count, list, total\n  periods: today, this_week, this_month, all\n  filters via extra:\n    {\"view\":\"renewals\"}   → services by expiry window (DEFAULT when unsure)\n    {\"view\":\"charges\"}    → money billed and outstanding\n    {\"view\":\"projects\"}   → project delivery status\n    {\"bucket\":\"overdue|today|week|month|active|expired|cancelled\"}  (renewals view)\n    {\"client\":\"<client name typed by the user>\"}\n  store_aware: NO — always set store_filter to null",
            'examples' => [
                '"How many services expired today?" → {"module":"projects","action":"count","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"renewals","bucket":"today"}}',
                '"Which renewals are overdue?" → {"module":"projects","action":"list","period":"all","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"renewals","bucket":"overdue"}}',
                '"What is expiring this week?" → {"module":"projects","action":"list","period":"all","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"renewals","bucket":"week"}}',
                '"How much is outstanding from clients?" → {"module":"projects","action":"total","period":"all","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"charges"}}',
                '"What work is running for Sharma Traders?" → {"module":"projects","action":"list","period":"all","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"renewals","bucket":"active","client":"Sharma Traders"}}',
                '"Show active projects" → {"module":"projects","action":"list","period":"all","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"projects"}}',
            ],
        ],
        'production' => [
            'doc' => "MODULE: production\n  Nursery/plant production floor. The plant batch is the centre — tasks, zones and workers all hang off it.\n  actions: count, list, total\n  periods: today, this_week, this_month, all\n  filters via extra:\n    {\"view\":\"tasks\"}      → daily activity tasks (DEFAULT when unsure)\n    {\"view\":\"batches\"}    → plant batches and their live quantity\n    {\"view\":\"workers\"}    → which employee is assigned to which zone\n    {\"task_status\":\"pending|done|skipped\"}\n    {\"activity_type\":\"watering|fertilizer|inspection|dead_check|pruning|sorting|spraying|pest_control|other\"}\n    {\"zone\":\"<zone name typed by the user>\"}\n    {\"employee\":\"<employee name typed by the user>\"}\n  store_aware: NO — always set store_filter to null",
            'examples' => [
                '"What is happening in production today?" → {"module":"production","action":"list","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"tasks"}}',
                '"How many tasks are still pending today?" → {"module":"production","action":"count","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"tasks","task_status":"pending"}}',
                '"Who is working in Block A?" → {"module":"production","action":"list","period":"all","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"workers","zone":"Block A"}}',
                '"How many tasks did Ramesh complete today?" → {"module":"production","action":"count","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"tasks","task_status":"done","employee":"Ramesh"}}',
                '"Show active plant batches" → {"module":"production","action":"list","period":"all","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"batches"}}',
                '"Watering done today?" → {"module":"production","action":"count","period":"today","status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{"view":"tasks","activity_type":"watering"}}',
            ],
        ],
        'suppliers' => [
            'doc' => "MODULE: suppliers\n  actions: count, list\n  store_aware: NO — always set store_filter to null",
            'examples' => [
                '"Show supplier list" → {"module":"suppliers","action":"list","period":null,"status_filter":null,"payment_status_filter":null,"search":null,"store_filter":null,"extra":{}}',
            ],
        ],
    ];


    // ── Predefined quick questions shown in UI ────────────────────────────────
    // Format: ['label' => '...', 'message' => '...']
    // Frontend can call GET /admin/ai-chatbot/quick-questions to fetch this list.
    public static function quickQuestions(): array
    {
        return [

            // Orders
            [
                'label'   => 'Today Orders',
                'message' => 'How many orders were received today?',
            ],

            // Expenses
            [
                'label'   => 'This Month Expenses',
                'message' => 'What are the total expenses for this month?',
            ],

            // Inventory
            [
                'label'   => 'Low Stock Products',
                'message' => 'Show low stock products.',
            ],

            // CRM
            [
                'label'   => 'Follow-up Reminders',
                'message' => 'Show pending follow-up leads.',
            ],

            // Purchases
            [
                'label'   => 'This Month Purchases',
                'message' => 'Show total purchases for this month.',
            ],

            // Production
            [
                'label'   => "Today's Production",
                'message' => 'What is happening in production today?',
            ],

            // Projects
            [
                'label'   => 'Expiring Renewals',
                'message' => 'What is expiring this week?',
            ],

        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONSTRUCTOR
    // ─────────────────────────────────────────────────────────────────────────

    public function __construct(
        protected AiService        $ai,
        protected AiContextBuilder $contextBuilder,
    ) {
        //
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC ENTRY POINT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Short memory window (last few turns, oldest→newest) for the current turn.
     * Injected by chat() and consumed by routeIntent() to resolve follow-ups.
     *
     * @var array<int,array{role:string,content:string}>
     */
    protected array $history = [];

    /**
     * Public entry point called by the controller.
     * Resets token accounting, threads in the short memory window, runs the
     * layered pipeline, then reports the combined Gemini token cost.
     *
     * @param array<int,array{role:string,content:string}> $history recent turns
     * @return array{success:bool,reply:string,tokens:int}
     */
    public function chat(string $message, string $language = 'en', array $history = []): array
    {
        $this->ai->resetUsage();
        $this->history = $history;

        $result = $this->respond($message, $language);

        // Combined tokens across intent-routing + reply-formatting calls.
        // Layer 1 (small talk) and Layer 2 (PHP router) never call Gemini → 0.
        $result['tokens'] = $this->ai->totalUsage()['total'] ?? 0;

        return $result;
    }

    /**
     * Layered pipeline: small-talk → PHP router → Gemini.
     * Returns ['success','reply']; chat() adds token accounting.
     */
    protected function respond(string $message, string $language = 'en'): array
    {
        // Guard: message length limit
        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            return [
                'success' => false,
                'reply'   => 'Your message is too long. Please keep it under 600 characters.',
            ];
        }

        // Layers 1 and 2 reply from English-only PHP templates. Running them for
        // a Hindi/Gujarati/Hinglish user produced an English answer while the
        // same question phrased differently fell through to Gemini and came back
        // translated — the same user got two different languages at random.
        // Non-English goes straight to Gemini: more tokens, but one language.
        $phpLayersAllowed = $language === 'en';

        // ── Layer 1: PHP Small Talk Handler ──────────────────────────────────
        // Greetings, thanks, farewells, acknowledgements answered instantly.
        // Zero Gemini calls. Zero DB queries. Zero tokens consumed.
        if ($phpLayersAllowed) {
            $smallTalkReply = PhpSmallTalkHandler::handle($message, $language);
            if ($smallTalkReply !== null) {
                return ['success' => true, 'reply' => $smallTalkReply];
            }
        }

        // Build context — deferred to here so Layer 1 bypasses it entirely.
        // Contains: user role, active store, allowed stores, enabled modules, language.
        $ctx = $this->contextBuilder->build($language);

        // ── Layer 2: PHP ERP Command Router ──────────────────────────────────
        // Common deterministic ERP queries handled directly by PHP.
        // Runs authorizeQuery + executeQuery normally; only formatReply is PHP.
        // Zero Gemini calls. Zero tokens consumed.
        try {
            $erpPlan = $phpLayersAllowed
                ? PhpErpCommandRouter::matchPlan($message, $ctx)
                : null;

            if ($erpPlan !== null) {
                // Authorization — same check as Gemini path
                $denial = $this->authorizeQuery($erpPlan, $ctx);
                if ($denial !== null) {
                    return ['success' => true, 'reply' => $denial];
                }

                // Execute — same Eloquent query engine as Gemini path
                $data = $this->executeQuery($erpPlan, $ctx);

                // Format — PHP templates, no Gemini
                $reply = PhpResponseFormatter::format($data, $erpPlan, $ctx);

                if ($reply !== null) {
                    return ['success' => true, 'reply' => $reply];
                }
                // PhpResponseFormatter returned null (edge case) → fall through to Gemini
            }
        } catch (Exception $e) {
            // Layer 2 failure must never block the user — fall through to Gemini
            Log::warning('AiChatbotService: PHP Fast Router failed, falling back to Gemini', [
                'error'   => $e->getMessage(),
                'message' => $message,
            ]);
        }

        // ── Layer 3: Gemini ───────────────────────────────────────────────────
        // Only reached for complex queries: analysis, comparison, reasoning,
        // recommendations, or anything Layer 1 / Layer 2 did not handle.
        try {
            // Step 1: Understand intent (AI → JSON plan)
            $plan = $this->routeIntent($message, $ctx);

            if (($plan['module'] ?? null) === 'unsupported_action') {
                return [
                    'success' => true,
                    'reply' => $this->handleUnsupportedAction($message, $ctx),
                ];
            }

            // Step 2: Authorize — module enabled? user has permission?
            // Returns a denial string if blocked, null if allowed.
            $denial = $this->authorizeQuery($plan, $ctx);
            if ($denial !== null) {
                return ['success' => true, 'reply' => $denial];
            }

            // Step 3: Fetch ERP data (pure Eloquent — read-only)
            $data = $this->executeQuery($plan, $ctx);

            // Step 4: Format human reply (AI)
            $reply = $this->formatReply($message, $plan, $data, $ctx);

            return ['success' => true, 'reply' => $reply];

        } catch (Exception $e) {
            Log::error('AiChatbotService Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => false,
                'reply'   => "I'm unable to process your request right now. Please try again in a few moments.",
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 1 — INTENT ROUTER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send user message to AI. AI returns a JSON plan that tells us:
     *  - which ERP module to query
     *  - what action (total / count / list / search)
     *  - what time period
     *  - any filters (status, search keyword)
     */
   protected function routeIntent(string $message, array $ctx = []): array
    {
        // Build store context — AI uses this to resolve "Junagadh store" → store_id
        $storeCtxStr = AiContextBuilder::storeContextString($ctx['store'] ?? []);

        // ── Short memory window ──────────────────────────────────────────────
        // Only the last couple of turns, so a follow-up like "and this month?"
        // can be resolved. Kept tiny on purpose — every line here is billed.
        $historyStr = '';
        foreach ($this->history as $turn) {
            $role    = ($turn['role'] ?? '') === 'assistant' ? 'Assistant' : 'User';
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content !== '') {
                $historyStr .= $role . ': ' . $content . "\n";
            }
        }
        $recentBlock = $historyStr === ''
            ? ''
            : "RECENT CONVERSATION (use ONLY to resolve follow-up references such as \"and this month?\" — the LATEST user question below is what you must classify):\n{$historyStr}\n";

        // ── Trim the prompt to only the modules this tenant's plan has enabled ──
        // $ctx['modules']['enabled'] is keyed by has_module() slugs (e.g. 'invoicing',
        // 'crm', 'hrm') — built once by AiContextBuilder. A tenant with 5-7 of the
        // 12 AI modules enabled gets a proportionally smaller system prompt.
        $enabledModules = $ctx['modules']['enabled'] ?? [];

        $moduleDocs = [];
        $examples   = [];

        foreach (self::AI_MODULE_LIBRARY as $aiModule => $def) {
            $requiredModule = self::MODULE_PERMISSION_MAP[$aiModule]['module'] ?? null;

            // null = always available (orders, clients). Otherwise must be in plan.
            if ($requiredModule !== null && ! isset($enabledModules[$requiredModule])) {
                continue;
            }

            $moduleDocs[] = $def['doc'];
            $examples = array_merge($examples, $def['examples']);
        }

        $moduleDocsStr = implode("\n\n", $moduleDocs);
        $examplesStr   = implode("\n\n", $examples);

        $systemPrompt = <<<PROMPT
        You are the Intent Router for Plantiq AI, an intelligent business operations assistant.
        Plantiq is an all-in-one business operations and management platform (NOT an ERP).

        The user will ask a business question in English, Hindi, Gujarati, or Hinglish.

        Your ONLY job: classify the question and return a JSON plan. No explanation. No markdown. Pure JSON only.

        IMPORTANT ACTION RULES:

            Currently supported AI actions:
            - total
            - count
            - list
            - search

            Currently NOT supported:
            - create
            - update
            - delete
            - submit
            - save

            If the user asks to create, update, delete, or modify business records:
            return:
            {"module":"unsupported_action"}

            Never pretend that a business action was completed.

            Never claim records were created, updated, deleted, or saved unless backend execution exists.

        {$recentBlock}STORE CONTEXT — use this to resolve store/branch names in the user's question:
        {$storeCtxStr}
        Rule: If the user mentions a store or branch name, match it to the store_id above and populate store_filter.
        Rule: If no specific store is mentioned, set store_filter to null (system applies default store).
        Rule: For company-wide modules (crm_leads, clients, products, suppliers), always set store_filter to null.

        Available modules and their query actions:

        {$moduleDocsStr}

        MODULE: unknown
          Use this when the question is a greeting, small talk, or not related to business data.
          Return: {"module": "unknown"}

        JSON schema to return:
        {
          "module": "<module_name>",
          "action": "<action>",
          "period": "<period_or_null>",
          "status_filter": "<status_string_or_null>",
          "payment_status_filter": "<payment_status_or_null>",
          "search": "<keyword_or_null>",
          "store_filter": {"store_id": <integer_or_null>, "store_name": "<string_or_null>"},
          "extra": {}
        }

        Examples:
        {$examplesStr}

        "Hello" → {"module":"unknown"}
        PROMPT;

        try {
            $raw = $this->ai->ask($message, $systemPrompt, null, true);
            $raw = trim(preg_replace('/^```json|^```|```$/m', '', $raw));

            // Extract JSON safely
            $start = strpos($raw, '{');
            $end   = strrpos($raw, '}');
            if ($start !== false && $end !== false) {
                $decoded = json_decode(substr($raw, $start, $end - $start + 1), true);
                if (is_array($decoded) && isset($decoded['module'])) {
                    return $decoded;
                }
                // JSON found but missing 'module' key — log for debugging
                Log::warning('AiChatbot routeIntent: JSON missing module key', ['raw' => $raw]);
            } else {
                // AI did not return JSON at all — log the raw response
                Log::warning('AiChatbot routeIntent: no JSON in AI response', ['raw' => $raw]);
            }
        } catch (Exception $e) {
            // Check storage/logs/laravel.log for the real error
            Log::error('AiChatbot routeIntent EXCEPTION', [
                'error'   => $e->getMessage(),
                'message' => $message,
            ]);
        }

        return ['module' => 'unknown'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 2 — AUTHORIZATION GUARD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Check if the current user can access the requested AI module.
     *
     * Two checks in order:
     *   1. Plan module — is this feature enabled in their subscription?
     *   2. Role permission — does this user's role allow viewing this data?
     *
     * Returns null   → authorized, proceed.
     * Returns string → denial message in user's language, stop here.
     *
     * Owners bypass the permission check but NOT the module check.
     * Super admins bypass everything.
     */
    protected function authorizeQuery(array $plan, array $ctx): ?string
    {
        $aiModule = $plan['module'] ?? 'unknown';
        $language = $ctx['language'] ?? 'en';
        $isOwner  = $ctx['user']['is_owner'] ?? false;

        // 'unknown' module goes to handleUnknown() — no authorization needed
        if ($aiModule === 'unknown') {
            return null;
        }

        // Super admin bypasses everything
        if (is_super_admin()) {
            return null;
        }

        $map = self::MODULE_PERMISSION_MAP[$aiModule] ?? null;

        // AI module not in map — unknown/future module, block safely
        if ($map === null) {
            return $this->denyMessage('module_unknown', $aiModule, $language);
        }

        // ── Check 1: Plan Module ─────────────────────────────────────────────
        // Even owners cannot access modules not in their subscription plan.
        if ($map['module'] !== null && ! has_module($map['module'])) {
            return $this->denyMessage('plan', $map['module'], $language);
        }

        // ── Check 2: Role Permission ─────────────────────────────────────────
        // Owners skip permission check (they have full access within their plan).
        if (! $isOwner && $map['permission'] !== null && ! has_permission($map['permission'])) {
            return $this->denyMessage('permission', $map['permission'], $language);
        }

        return null; // ✅ Authorized
    }

    /**
     * Build a polite denial message based on denial type and user language.
     *
     * @param  string  $type     'plan' | 'permission' | 'module_unknown'
     * @param  string  $slug     The module or permission slug for context
     * @param  string  $language en|hi|gu|hinglish
     */
    protected function denyMessage(string $type, string $slug, string $language): string
    {
        // Human-readable module labels for denial messages
        $moduleLabels = [
            'invoicing'  => ['en' => 'Invoices & Quotations', 'hi' => 'इनवॉइस और कोटेशन',    'gu' => 'ઇન્વૉઇસ અને ક્વોટેશન',  'hinglish' => 'Invoices & Quotations'],
            'pos'        => ['en' => 'POS Billing',           'hi' => 'POS बिलिंग',             'gu' => 'POS બિલિંગ',             'hinglish' => 'POS Billing'],
            'purchases'  => ['en' => 'Purchases',             'hi' => 'खरीदारी',                'gu' => 'ખરીદી',                  'hinglish' => 'Purchases'],
            'inventory'  => ['en' => 'Inventory',             'hi' => 'इन्वेंटरी',              'gu' => 'ઇન્વેન્ટરી',            'hinglish' => 'Inventory'],
            'crm'        => ['en' => 'CRM & Leads',           'hi' => 'CRM और लीड्स',           'gu' => 'CRM અને લીડ્સ',          'hinglish' => 'CRM & Leads'],
            'hrm'        => ['en' => 'HRM & Attendance',      'hi' => 'HRM और अटेंडेंस',        'gu' => 'HRM અને હાજરી',          'hinglish' => 'HRM & Attendance'],
            'challan'    => ['en' => 'Challans',              'hi' => 'चालान',                  'gu' => 'ચલણ',                    'hinglish' => 'Challans'],
            'expenses'   => ['en' => 'Expenses',              'hi' => 'खर्च',                   'gu' => 'ખર્ચ',                   'hinglish' => 'Expenses'],
        ];

        $lang  = in_array($language, ['en', 'hi', 'gu', 'hinglish']) ? $language : 'en';
        $label = $moduleLabels[$slug][$lang] ?? strtoupper($slug);

        if ($type === 'plan') {
            return match ($lang) {
                'hi'       => "{$label} मॉड्यूल आपके मौजूदा सब्सक्रिप्शन प्लान में शामिल नहीं है। कृपया अपना प्लान अपग्रेड करें।",
                'gu'       => "{$label} મૉડ્યૂલ તમારી હાલની સબ્સ્ક્રિપ્શન પ્લાનમાં ઉપલબ્ધ નથી. કૃપા કરી તમારી પ્લાન અપગ્રેડ કરો.",
                'hinglish' => "{$label} module aapke current subscription plan me available nahi hai. Plan upgrade karo.",
                default    => "The {$label} module is not available in your current subscription plan. Please upgrade your plan.",
            };
        }

        if ($type === 'permission') {
            return match ($lang) {
                'hi'       => "आपके पास यह डेटा देखने की अनुमति नहीं है। अपने एडमिन से संपर्क करें।",
                'gu'       => "આ ડેટા જોવાની તમને પરવાનગી નથી. કૃપા કરી તમારા એડમિનનો સંપર્ક કરો.",
                'hinglish' => "Aapke paas yeh data dekhne ki permission nahi hai. Admin se contact karo.",
                default    => "You don't have permission to view this data. Please contact your administrator.",
            };
        }

        // module_unknown — safe fallback
        return match ($lang) {
            'hi'       => "मैं इस तरह का डेटा एक्सेस नहीं कर सकता। कृपया कोई अन्य प्रश्न पूछें।",
            'gu'       => "હું આ પ્રકારનો ડેટા ઍક્સેસ કરી શકતો નથી. કૃપા કરી બીજો પ્રશ્ન પૂછો.",
            'hinglish' => "Main is tarah ka data access nahi kar sakta. Koi aur question puchho.",
            default    => "I'm not able to access that type of data. Please ask a different question.",
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 3 — QUERY ENGINE (Read-Only Eloquent — No raw SQL, No writes)
    // ─────────────────────────────────────────────────────────────────────────

    protected function executeQuery(array $plan, array $ctx = []): array
    {
        $module    = $plan['module']  ?? 'unknown';
        $action    = $plan['action']  ?? 'count';
        $period    = $plan['period']  ?? null;
        $status    = $plan['status_filter'] ?? null;
        $payStatus = $plan['payment_status_filter'] ?? null;
        $search    = $plan['search']  ?? null;
        $extra     = $plan['extra']   ?? [];

        // Resolve which store_id(s) to apply — validates access rights, handles defaults
        $storeFilter = $this->resolveStoreFilter($plan, $ctx);

        return match ($module) {
            // ── Store-aware modules (pass $storeFilter) ───────────────────────
            'invoices'       => $this->queryInvoices($action, $period, $status, $payStatus, $search, $storeFilter),
            'orders'         => $this->queryOrders($action, $period, $status, $search, $storeFilter),
            'purchases'      => $this->queryPurchases($action, $period, $status, $payStatus, $storeFilter),
            'expenses'       => $this->queryExpenses($action, $period, $storeFilter),
            'quotations'     => $this->queryQuotations($action, $period, $status, $storeFilter),
            'payments'       => $this->queryPayments($action, $period, $status, $storeFilter),
            'challans'       => $this->queryChallans($action, $period, $status, $storeFilter),
            'hrm_attendance' => $this->queryAttendance($action, $status, $storeFilter),
            // ── Company-wide modules (NO store filter) ────────────────────────
            'crm_leads'      => $this->queryCrmLeads($action, $period, $extra),
            'clients'        => $this->queryClients($action, $search),
            'products'       => $this->queryProducts($action, $search, $storeFilter),
            'suppliers'      => $this->querySuppliers($action),
            'production'     => $this->queryProduction($action, $period, $extra),
            'projects'       => $this->queryProjects($action, $period, $extra),
            'unknown'        => ['module' => 'unknown'],
            default          => ['module' => 'unknown'],
        };
    }

    // ── Period helper — applied to any query ─────────────────────────────────

    private function applyPeriod($query, string $column, ?string $period)
    {
        return match ($period) {
            'today'      => $query->whereDate($column, now()->toDateString()),
            'this_week'  => $query->whereBetween($column, [now()->startOfWeek(), now()->endOfWeek()]),
            'this_month' => $query->whereMonth($column, now()->month)->whereYear($column, now()->year),
            'this_year'  => $query->whereYear($column, now()->year),
            default      => $query, // 'all' or null — no date filter
        };
    }

    // ── Store filter resolver ─────────────────────────────────────────────────

    /**
     * Determines which store_id(s) to apply to a query.
     *
     * Priority:
     *   1. User asked for specific store (AI extracted store_id from context)
     *      → Validate it belongs to company + user has access → use it
     *   2. No specific store → use default:
     *      Owner/admin → active (switched) store
     *      Staff       → their assigned store(s)
     *
     * Returns:
     *   ['ids' => [3],    'name' => 'Junagadh Store', 'specific' => true]
     *   ['ids' => [3, 5], 'name' => null,              'specific' => false]
     *   ['ids' => null,   'name' => null,              'specific' => false]  ← no filter
     */
    private function resolveStoreFilter(array $plan, array $ctx): array
    {
        $storeCtx   = $ctx['store']  ?? [];
        $allowedIds = $storeCtx['allowed_store_ids'] ?? null; // null = owner (all stores)
        $activeId   = $storeCtx['active_store_id']   ?? null;
        $allStores  = collect($storeCtx['all_stores'] ?? []);
        $planFilter = $plan['store_filter']           ?? null;

        // ── Case 1: User asked about a specific store ─────────────────────────
        if (! empty($planFilter['store_id'])) {
            $requestedId = (int) $planFilter['store_id'];

            // Security: store must belong to this company (Tenantable already loaded these)
            $storeExists = $allStores->contains('id', $requestedId);

            // Access: owner (null) = all stores, staff = only assigned stores
            $hasAccess = $allowedIds === null || in_array($requestedId, $allowedIds);

            if ($storeExists && $hasAccess) {
                return [
                    'ids'      => [$requestedId],
                    'name'     => $planFilter['store_name'] ?? null,
                    'specific' => true,
                ];
            }

            // Store not found or access denied → fall through to default silently
            Log::warning('AiChatbot: Requested store_id not accessible', [
                'requested_id' => $requestedId,
                'allowed_ids'  => $allowedIds,
                'user_id'      => Auth::id(),
            ]);
        }

        // ── Case 2: Default — no specific store mentioned ─────────────────────
        if ($allowedIds === null) {
            // Owner/admin — use their currently active (switched) store
            return [
                'ids'      => $activeId ? [$activeId] : null,
                'name'     => $storeCtx['active_store_name'] ?? null,
                'specific' => false,
            ];
        }

        // Staff — restricted to their assigned stores only
        // [0] guard: empty assigned stores → match nothing (not "match all")
        return [
            'ids'      => empty($allowedIds) ? [0] : $allowedIds,
            'name'     => null,
            'specific' => false,
        ];
    }

    /**
     * Apply store_id filter to any Eloquent query builder.
     * Safe to call on any model that has a store_id column.
     */
    private function applyStoreFilter($query, array $storeFilter)
    {
        $ids = $storeFilter['ids'] ?? null;

        // Company boundary, always — independent of the store filter.
        //
        // Returning the query untouched below is only safe for models carrying
        // the Tenantable trait. Anything without it was reaching the database
        // with no tenant condition at all, so the company condition is applied
        // here rather than trusted to each caller.
        if ($companyId = Auth::user()?->company_id) {
            $query->where($query->getModel()->getTable().'.company_id', $companyId);
        }

        if ($ids === null) {
            return $query; // Owner with no active store — company filter still stands.
        }

        return count($ids) === 1
            ? $query->where('store_id', $ids[0])
            : $query->whereIn('store_id', $ids);
    }

    // ── Invoices ─────────────────────────────────────────────────────────────

    private function queryInvoices(string $action, ?string $period, ?string $status, ?string $payStatus, ?string $search, array $storeFilter = []): array
    {
        // Tenantable trait auto-applies company_id scope
        $q = Invoice::query();

        // Store filter — narrows to specific store or user's accessible stores
        $q = $this->applyStoreFilter($q, $storeFilter);

        if ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Map common terms to correct DB values
        if ($status) {
            $q->where('status', $status);
        }

        if ($payStatus) {
            // Map "unpaid" → payment_status check for both unpaid and overdue
            if ($payStatus === 'unpaid') {
                $q->whereIn('payment_status', ['unpaid', 'overdue', 'partial']);
            } else {
                $q->where('payment_status', $payStatus);
            }
        }

        $q = $this->applyPeriod($q, 'invoice_date', $period);

        if ($action === 'total') {
            return [
                'module'        => 'invoices',
                'action'        => 'total',
                'period'        => $period,
                'grand_total'   => (float) $q->sum('grand_total'),
                'count'         => $q->count(),
            ];
        }

        if ($action === 'count') {
            return [
                'module'  => 'invoices',
                'action'  => 'count',
                'period'  => $period,
                'count'   => $q->count(),
            ];
        }

        // list / search
        $rows = $q->latest('invoice_date')
            ->limit(self::MAX_LIST_ROWS)
            ->get(['invoice_number', 'customer_name', 'grand_total', 'status', 'payment_status', 'invoice_date'])
            ->toArray();

        return [
            'module' => 'invoices',
            'action' => 'list',
            'period' => $period,
            'count'  => count($rows),
            'rows'   => $rows,
        ];
    }

    // ── Orders ───────────────────────────────────────────────────────────────

    private function queryOrders(string $action, ?string $period, ?string $status, ?string $search, array $storeFilter = []): array
    {
        $q = Order::query();
        $q = $this->applyStoreFilter($q, $storeFilter);

        if ($status) {
            $q->where('status', $status);
        }

        if ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $q = $this->applyPeriod($q, 'created_at', $period);

        if ($action === 'total') {
            return [
                'module'      => 'orders',
                'action'      => 'total',
                'period'      => $period,
                'grand_total' => (float) $q->sum('total_amount'),
                'count'       => $q->count(),
            ];
        }

        if ($action === 'count') {
            return [
                'module' => 'orders',
                'action' => 'count',
                'period' => $period,
                'count'  => $q->count(),
            ];
        }

        $rows = $q->latest()
            ->limit(self::MAX_LIST_ROWS)
            ->get(['order_number', 'customer_name', 'total_amount', 'status', 'payment_status', 'created_at'])
            ->toArray();

        return [
            'module' => 'orders',
            'action' => 'list',
            'period' => $period,
            'count'  => count($rows),
            'rows'   => $rows,
        ];
    }

    // ── Purchases ────────────────────────────────────────────────────────────

    private function queryPurchases(
        string $action,
        ?string $period,
        ?string $status,
        ?string $payStatus,
        array $storeFilter = []
    ): array {
        $q = Purchase::query();
        $q = $this->applyStoreFilter($q, $storeFilter);

        if ($status) {
            $q->where('status', $status);
        }

        if ($payStatus) {
            $q->where('payment_status', $payStatus);
        }

        $q = $this->applyPeriod($q, 'purchase_date', $period);

        if ($action === 'count') {
            $cloned = clone $q;
            return [
                'module'         => 'purchases',
                'action'         => 'count',
                'period'         => $period,
                'status'         => $status,
                'payment_status' => $payStatus,
                'count'          => $cloned->count(),
                'grand_total'    => (float) $cloned->sum('total_amount'),
            ];
        }

        if ($action === 'list') {
            $totalCount = (clone $q)->count(); // total matching records before limit
            $rows = $q->with('supplier:id,name')
                ->latest('purchase_date')
                ->limit(self::MAX_LIST_ROWS)
                ->get([
                    'id',
                    'supplier_id',
                    'purchase_number',
                    'status',
                    'payment_status',
                    'total_amount',
                    'purchase_date',
                ])
                ->toArray();

            return [
                'module'         => 'purchases',
                'action'         => 'list',
                'period'         => $period,
                'status'         => $status,
                'payment_status' => $payStatus,
                'count'          => $totalCount,
                'grand_total'    => (float) (clone $q)->sum('total_amount'),
                'rows'           => $rows,
            ];
        }

        return [
            'module'      => 'purchases',
            'action'      => 'total',
            'period'      => $period,
            'status'      => $status,
            'payment_status' => $payStatus,
            'grand_total' => (float) $q->sum('total_amount'),
            'count'       => $q->count(),
        ];
    }

    // ── Expenses ─────────────────────────────────────────────────────────────

    private function queryExpenses(string $action, ?string $period, array $storeFilter = []): array
    {
        $q = Expense::query();
        $q = $this->applyStoreFilter($q, $storeFilter);
        $q = $this->applyPeriod($q, 'expense_date', $period);

        return [
            'module'      => 'expenses',
            'action'      => $action,
            'period'      => $period,
            'grand_total' => (float) $q->sum('total_amount'),
            'count'       => $q->count(),
        ];
    }

    // ── CRM Leads ────────────────────────────────────────────────────────────

    private function queryCrmLeads(string $action, ?string $period, array $extra): array
    {
        $q = CrmLead::query();

        // Converted filter
        if (isset($extra['is_converted'])) {
            $q->where('is_converted', (bool) $extra['is_converted']);
        } else {
            // Default: show only active (not converted) leads
            $q->where('is_converted', false);
        }

        // Hot leads: reuse CrmLead::scopeHot() — defined in CrmLead model as:
        // priority = 'hot' OR score >= 50
        // DO NOT hardcode score >= 50 alone — misses priority='hot' leads
        if (!empty($extra['score_hot'])) {
            $q->hot(); // calls CrmLead::scopeHot() — the authoritative hot lead definition
        }

        // Follow-up overdue: reuse CrmLead::scopeOverdue() — defined in CrmLead model as:
        // next_followup_at IS NOT NULL AND next_followup_at < now() AND is_converted = false
        if (!empty($extra['followup_pending'])) {
            $q->overdue(); // calls CrmLead::scopeOverdue() — no manual column assumptions needed
        }

        $q = $this->applyPeriod($q, 'created_at', $period);

        if ($action === 'count') {
            return [
                'module' => 'crm_leads',
                'action' => 'count',
                'period' => $period,
                'count'  => $q->count(),
                'extra'  => $extra,
            ];
        }

        // list
        $rows = $q->latest()
            ->limit(self::MAX_LIST_ROWS)
            ->get(['name', 'company_name', 'phone', 'score', 'next_followup_at', 'created_at'])
            ->toArray();

        return [
            'module' => 'crm_leads',
            'action' => 'list',
            'period' => $period,
            'count'  => count($rows),
            'rows'   => $rows,
            'extra'  => $extra,
        ];
    }

    // ── HRM Attendance ───────────────────────────────────────────────────────

    private function queryAttendance(string $action, ?string $status, array $storeFilter = []): array
    {
        // Always today's attendance
        $q = Attendance::query()->whereDate('date', today());
        $q = $this->applyStoreFilter($q, $storeFilter);

        if ($status) {
            $q->where('status', $status);
        }

        if ($action === 'count') {
            return [
                'module' => 'hrm_attendance',
                'action' => 'count',
                'status' => $status,
                'count'  => $q->count(),
            ];
        }

        // list — join with employee name via relationship
        $rows = $q->with('employee:id,employee_code')
            ->with('employee.user:id,name')
            ->limit(self::MAX_LIST_ROWS)
            ->get(['id', 'employee_id', 'status', 'check_in_time', 'check_out_time', 'worked_hours'])
            ->map(function ($att) {
                return [
                    'name'          => optional(optional($att->employee)->user)->name ?? 'N/A',
                    'employee_code' => optional($att->employee)->employee_code ?? '',
                    'status'        => $att->status,
                    'check_in'      => $att->check_in_time?->format('h:i A'),
                    'check_out'     => $att->check_out_time?->format('h:i A'),
                    'worked_hours'  => $att->worked_hours,
                ];
            })->toArray();

        return [
            'module' => 'hrm_attendance',
            'action' => 'list',
            'status' => $status,
            'count'  => count($rows),
            'rows'   => $rows,
        ];
    }

    // ── Clients ──────────────────────────────────────────────────────────────

    private function queryClients(string $action, ?string $search): array
    {
        $q = Client::query();

        if ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($action === 'count') {
            return ['module' => 'clients', 'action' => 'count', 'count' => $q->count()];
        }

        $rows = $q->latest()->limit(self::MAX_LIST_ROWS)
            ->get(['name', 'company_name', 'email', 'phone'])
            ->toArray();

        return ['module' => 'clients', 'action' => 'list', 'count' => count($rows), 'rows' => $rows];
    }

    // ── Products ─────────────────────────────────────────────────────────────    
    private function queryProducts(string $action, ?string $search, array $storeFilter = []): array
    {
        $q = Product::query()->where('is_active', true);

        if ($search) {
            // Search by product name OR sku code — reuses existing ProductService search pattern
            $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('skus', function ($skuQuery) use ($search) {
                      $skuQuery->where('sku', 'like', "%{$search}%")
                               ->orWhere('barcode', 'like', "%{$search}%");
                  });
            });
        }

        if ($action === 'count') {
            return ['module' => 'products', 'action' => 'count', 'count' => $q->count()];
        }

        // List: eager load skus to get sku code + price without assuming columns on products table
        // product_skus.price = selling price, product_skus.sku = SKU code (NOT on products table)
        $storeIds = $storeFilter['ids'] ?? [];

            $rows = $q->with([
                'skus' => function ($skuQuery) use ($storeIds) {
                    $skuQuery->where('is_active', true)
                        ->select('id', 'product_id', 'sku', 'price', 'cost')
                        ->with([
                            'stocks' => function ($sq) use ($storeIds) {
                                if (!empty($storeIds)) {
                                    $sq->whereHas('warehouse', function ($wq) use ($storeIds) {
                                        $wq->whereIn('store_id', $storeIds);
                                    });
                                }
                            }
                        ]) // stock filtered to store warehouses only
                        ->limit(3); // 3 SKUs for variable products
                }
            ])
            ->latest()
            ->limit(self::MAX_LIST_ROWS)
            ->get(['id', 'name', 'type'])
            ->map(function ($product) {
                if ($product->type === 'variable') {
                    // Variable: list each SKU with its own stock
                    $skus = $product->skus->map(fn ($sku) => [
                        'sku_code'    => $sku->sku ?? 'N/A',
                        'price'       => $sku->price,
                        'total_stock' => (int) $sku->stocks->sum('qty'),
                    ])->toArray();

                    return [
                        'name'        => $product->name,
                        'type'        => 'variable',
                        'skus'        => $skus,
                        'total_stock' => array_sum(array_column($skus, 'total_stock')),
                    ];
                }

                // Single product
                $firstSku = $product->skus->first();
                return [
                    'name'        => $product->name,
                    'type'        => 'single',
                    'sku_code'    => $firstSku?->sku   ?? 'N/A',
                    'price'       => $firstSku?->price ?? null,
                    'total_stock' => $firstSku ? (int) $firstSku->stocks->sum('qty') : 0,
                ];
            })
            ->toArray();

        return ['module' => 'products', 'action' => 'list', 'count' => count($rows), 'rows' => $rows];
    }

    // ── Suppliers ────────────────────────────────────────────────────────────

    /**
     * Production floor — everything routed through the plant batch.
     *
     * Three views over the same story: what work is due (tasks), what is growing
     * (batches), and who is on which zone (workers). Kept in one AI module
     * because the user asks about them in one breath — "what's happening today"
     * means tasks, but the follow-up is always a zone or a person.
     *
     * Not store-aware: production_daily_tasks has no store_id. Zones belong to
     * production sites, which are company-level.
     */
    private function queryProduction(string $action, ?string $period, array $extra): array
    {
        $view = $extra['view'] ?? 'tasks';

        return match ($view) {
            'batches' => $this->queryProductionBatches($action),
            'workers' => $this->queryProductionWorkers($extra),
            default   => $this->queryProductionTasks($action, $period, $extra),
        };
    }

    private function queryProductionTasks(string $action, ?string $period, array $extra): array
    {
        // Tenantable applies company_id. due_date is the working day, so the
        // period filter goes on it rather than created_at — a task generated
        // last night for today is today's work.
        $q = DailyTask::query()->with([
            'zone:id,name',
            'completedBy:id,first_name,last_name',
            'plantBatch:id,batch_code',
        ]);

        $q = $this->applyPeriod($q, 'due_date', $period ?? 'today');

        if (! empty($extra['task_status'])) {
            $q->where('status', $extra['task_status']);
        }

        if (! empty($extra['activity_type'])) {
            $q->where('activity_type', $extra['activity_type']);
        }

        if (! empty($extra['zone'])) {
            $zoneName = $extra['zone'];
            $q->whereHas('zone', fn ($z) => $z->where('name', 'like', "%{$zoneName}%"));
        }

        // Employee names arrive as free text from the user, so match loosely
        // against the completer rather than expecting an ID.
        if (! empty($extra['employee'])) {
            $name = $extra['employee'];
            $q->whereHas('completedBy', fn ($e) => $e
                ->where('first_name', 'like', "%{$name}%")
                ->orWhere('last_name', 'like', "%{$name}%"));
        }

        // A single count is not enough for "what is happening today" — the
        // status split is what the manager actually wants to hear.
        $breakdown = (clone $q)->toBase()
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'done'    THEN 1 ELSE 0 END) AS done,
                SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) AS skipped
            ")->first();

        $result = [
            'module'    => 'production',
            'view'      => 'tasks',
            'period'    => $period ?? 'today',
            'total'     => (int) ($breakdown->total ?? 0),
            'pending'   => (int) ($breakdown->pending ?? 0),
            'done'      => (int) ($breakdown->done ?? 0),
            'skipped'   => (int) ($breakdown->skipped ?? 0),
        ];

        if ($action === 'count' || $action === 'total') {
            return $result;
        }

        $result['records'] = $q->orderBy('status')
            ->orderBy('zone_id')
            ->limit(self::MAX_LIST_ROWS)
            ->get()
            ->map(fn ($t) => [
                'activity'  => $t->activity_type?->value ?? (string) $t->activity_type,
                'status'    => $t->status?->value ?? (string) $t->status,
                'zone'      => $t->zone?->name,
                'batch'     => $t->plantBatch?->batch_code,
                'done_by'   => $t->completedBy?->full_name,
                'done_at'   => $t->completed_at?->format('h:i A'),
            ])
            ->toArray();

        return $result;
    }

    private function queryProductionBatches(string $action): array
    {
        $q = PlantBatch::query()->with('product:id,name')->where('status', 'active');

        $result = [
            'module' => 'production',
            'view'   => 'batches',
            'count'  => (clone $q)->count(),
            // Live plant count across the floor, not the quantity ever started.
            'total_quantity' => (int) (clone $q)->sum('current_quantity'),
        ];

        if ($action === 'count' || $action === 'total') {
            return $result;
        }

        $result['records'] = $q->orderByDesc('id')
            ->limit(self::MAX_LIST_ROWS)
            ->get()
            ->map(fn ($b) => [
                'batch_code' => $b->batch_code,
                'product'    => $b->product?->name,
                'quantity'   => $b->current_quantity,
                'started'    => $b->batch_start_datetime?->format('d M Y'),
            ])
            ->toArray();

        return $result;
    }

    private function queryProductionWorkers(array $extra): array
    {
        // Only live assignments — a released worker is not "working here".
        $q = ZoneAssignment::query()
            ->with('employee:id,first_name,last_name', 'zone:id,name')
            ->where('is_active', true);

        if (! empty($extra['zone'])) {
            $zoneName = $extra['zone'];
            $q->whereHas('zone', fn ($z) => $z->where('name', 'like', "%{$zoneName}%"));
        }

        if (! empty($extra['employee'])) {
            $name = $extra['employee'];
            $q->whereHas('employee', fn ($e) => $e
                ->where('first_name', 'like', "%{$name}%")
                ->orWhere('last_name', 'like', "%{$name}%"));
        }

        return [
            'module'  => 'production',
            'view'    => 'workers',
            'count'   => (clone $q)->count(),
            'records' => $q->limit(self::MAX_LIST_ROWS)
                ->get()
                ->map(fn ($a) => [
                    'employee' => $a->employee?->full_name,
                    'zone'     => $a->zone?->name,
                    'since'    => $a->assigned_at?->format('d M Y'),
                ])
                ->toArray(),
        ];
    }

    /**
     * Client work — renewals, money and delivery.
     *
     * Renewals is the default view because that is what the module is for: a
     * service's current_period_end is its renewal date, so "what expired today"
     * and "what is running" are the same query with a different window.
     *
     * Buckets reuse ProjectClientService::scopeRenewalBucket() rather than
     * rebuilding the date logic here — if the two ever drift, the chatbot and
     * the renewal board would quietly disagree about the same day.
     *
     * Not store-aware: a client's outstanding balance spans every store.
     */
    private function queryProjects(string $action, ?string $period, array $extra): array
    {
        $view = $extra['view'] ?? 'renewals';

        return match ($view) {
            'charges'  => $this->queryProjectCharges($action, $period, $extra),
            'projects' => $this->queryProjectList($action, $extra),
            default    => $this->queryProjectRenewals($action, $extra),
        };
    }

    private function queryProjectRenewals(string $action, array $extra): array
    {
        $bucket = $extra['bucket'] ?? 'week';

        $q = ProjectClientService::query()
            ->with('client:id,name,phone', 'project:id,title')
            ->renewalBucket($bucket);

        if (! empty($extra['client'])) {
            $name = $extra['client'];
            $q->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$name}%"));
        }

        $result = [
            'module' => 'projects',
            'view'   => 'renewals',
            'bucket' => $bucket,
            'count'  => (clone $q)->count(),
        ];

        if ($action === 'count') {
            return $result;
        }

        // Soonest first. On the overdue bucket that surfaces the longest-lapsed
        // service, which is the one most likely to have been forgotten.
        $result['records'] = $q->orderByRaw('current_period_end IS NULL, current_period_end ASC')
            ->limit(self::MAX_LIST_ROWS)
            ->get()
            ->map(fn ($cs) => [
                'client'      => $cs->client?->name,
                'service'     => $cs->name ?: 'Service',
                'expires_on'  => $cs->current_period_end?->format('d M Y'),
                'days_left'   => $cs->days_until_expiry,
                'cycle'       => $cs->billing_cycle?->value,
                'next_price'  => (float) $cs->price,
                'status'      => $cs->status?->value,
            ])
            ->toArray();

        return $result;
    }

    private function queryProjectCharges(string $action, ?string $period, array $extra): array
    {
        $q = ProjectCharge::query()->with('client:id,name');

        $q = $this->applyPeriod($q, 'charge_date', $period);

        if (! empty($extra['client'])) {
            $name = $extra['client'];
            $q->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$name}%"));
        }

        // Cancelled charges are not money owed and must never enter a total.
        $q->where('status', '!=', 'cancelled');

        $totals = (clone $q)->toBase()->selectRaw('
            COUNT(*) AS cnt,
            COALESCE(SUM(total_amount), 0) AS billed,
            COALESCE(SUM(paid_amount), 0) AS paid,
            COALESCE(SUM(written_off_amount), 0) AS written_off
        ')->first();

        $billed = (float) ($totals->billed ?? 0);
        $paid   = (float) ($totals->paid ?? 0);
        $off    = (float) ($totals->written_off ?? 0);

        $result = [
            'module'      => 'projects',
            'view'        => 'charges',
            'count'       => (int) ($totals->cnt ?? 0),
            'billed'      => $billed,
            'paid'        => $paid,
            'written_off' => $off,
            // Written-off money was forgiven, not collected — it leaves the
            // outstanding figure without ever counting as received.
            'outstanding' => round($billed - $paid - $off, 2),
        ];

        if ($action === 'count' || $action === 'total') {
            return $result;
        }

        $result['records'] = $q->whereIn('status', ['pending', 'partially_paid'])
            ->orderBy('due_date')
            ->limit(self::MAX_LIST_ROWS)
            ->get()
            ->map(fn ($ch) => [
                'client'   => $ch->client?->name,
                'title'    => $ch->title,
                'total'    => (float) $ch->total_amount,
                'due'      => round((float) $ch->total_amount - (float) $ch->paid_amount - (float) $ch->written_off_amount, 2),
                'due_date' => $ch->due_date?->format('d M Y'),
                'status'   => $ch->status?->value,
            ])
            ->toArray();

        return $result;
    }

    private function queryProjectList(string $action, array $extra): array
    {
        $q = Project::query()->with('client:id,name')->where('status', 'active');

        if (! empty($extra['client'])) {
            $name = $extra['client'];
            $q->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$name}%"));
        }

        $result = [
            'module' => 'projects',
            'view'   => 'projects',
            'count'  => (clone $q)->count(),
        ];

        if ($action === 'count') {
            return $result;
        }

        $result['records'] = $q->orderByDesc('id')
            ->limit(self::MAX_LIST_ROWS)
            ->get()
            ->map(fn ($p) => [
                'title'      => $p->title,
                'client'     => $p->client?->name,
                'started'    => $p->start_date?->format('d M Y'),
                'expected'   => $p->expected_end_date?->format('d M Y'),
            ])
            ->toArray();

        return $result;
    }

    private function querySuppliers(string $action): array
    {
        $count = Supplier::query()->count();
        return ['module' => 'suppliers', 'action' => 'count', 'count' => $count];
    }

    // ── Payments ─────────────────────────────────────────────────────────────

    private function queryPayments(string $action, ?string $period, ?string $status, array $storeFilter = []): array
    {
        $q = Payment::query();
        $q = $this->applyStoreFilter($q, $storeFilter);

        // Sales and purchase documents only. The payments table is shared by
        // every module, so this stays a whitelist: a new module writing to it
        // must be added here deliberately rather than leaking into totals the
        // moment it ships.
        $q->whereIn('paymentable_type', [
            Invoice::class,            
            Purchase::class,
        ]);

        if ($status) {
            $q->where('status', $status);
        }

        $q = $this->applyPeriod($q, 'payment_date', $period);

        return [
            'module'      => 'payments',
            'action'      => $action,
            'period'      => $period,
            'grand_total' => (float) $q->sum('amount_received'),
            'count'       => $q->count(),
        ];
    }

    // ── Quotations ───────────────────────────────────────────────────────────

    private function queryQuotations(string $action, ?string $period, ?string $status, array $storeFilter = []): array
    {
        // Explicit company condition kept alongside Tenantable's global scope.
        // That scope registers only when a user is authenticated, and this
        // query previously had no tenant condition at all, so the boundary is
        // stated here rather than assumed.
        $q = Quotation::query()->where('company_id', Auth::user()->company_id);

        $q = $this->applyStoreFilter($q, $storeFilter);

        if ($status) {
            $q->where('status', $status);
        }

        $q = $this->applyPeriod($q, 'quotation_date', $period);

        if ($action === 'count') {
            return ['module' => 'quotations', 'action' => 'count', 'period' => $period, 'count' => $q->count()];
        }

        $rows = $q->latest('quotation_date')->limit(self::MAX_LIST_ROWS)
            ->get(['quotation_number', 'customer_name', 'grand_total', 'status', 'quotation_date'])
            ->toArray();

        return ['module' => 'quotations', 'action' => 'list', 'period' => $period, 'count' => count($rows), 'rows' => $rows];
    }

    // ── Challans ─────────────────────────────────────────────────────────────

    private function queryChallans(string $action, ?string $period, ?string $status, array $storeFilter = []): array
    {
        $q = Challan::query();
        $q = $this->applyStoreFilter($q, $storeFilter);

        if ($status) {
            $q->where('status', $status);
        }

        $q = $this->applyPeriod($q, 'challan_date', $period);

        if ($action === 'count') {
            return ['module' => 'challans', 'action' => 'count', 'period' => $period, 'count' => $q->count()];
        }

        $rows = $q->latest('challan_date')->limit(self::MAX_LIST_ROWS)
            ->get(['challan_number', 'party_name', 'status', 'challan_date'])
            ->toArray();

        return ['module' => 'challans', 'action' => 'list', 'period' => $period, 'count' => count($rows), 'rows' => $rows];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 3 — ANSWER FORMATTER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Pass original question + fetched ERP data to AI.
     * AI returns a clean, human-friendly answer in Hinglish/English.
     */
    protected function formatReply(string $message, array $plan, array $data, array $ctx = []): string
    {
        $module   = $plan['module'] ?? 'unknown';
        $language = $ctx['language'] ?? 'en';
        $langInstruction = AiContextBuilder::languageInstruction($language);

        // No data needed — handle general/unknown gracefully
        if ($module === 'unknown') {
            return $this->handleUnknown($message, $ctx);
        }

        $dataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<PROMPT
        You are Plantiq AI, an intelligent business operations assistant developed by Qlinkon Technology.
        Plantiq is an all-in-one business operations and management platform.

        SECURITY & BEHAVIOR RULES:
        - NEVER mention Gemini, Google, OpenAI, ChatGPT, LLMs, or any AI vendors.
        - NEVER expose SQL, JSON, Eloquent, database structure, or backend architecture.
        - NEVER modify, calculate, or hallucinate financial totals/counts. Trust the backend Data exactly.
        - Only summarize the provided Business Data. Do not make up answers.
        - Keep replies short and clear — 2 to 5 lines max, unless listing records.
        - Use ₹ for all currency amounts. Format large numbers with commas.
        - If count is 0, say it politely.
        - When listing records, use a simple numbered list.

        CRITICAL — LANGUAGE (override all defaults):
        {$langInstruction}
        PROMPT;

        $userPrompt = "User asked: {$message}\n\nBusiness Data:\n{$dataJson}";

        try {
            return trim($this->ai->ask($userPrompt, $systemPrompt));
        } catch (Exception $e) {
            Log::error('AiChatbot Format Reply Error: ' . $e->getMessage());
            return 'I fetched your data but had trouble formatting the reply. Please try again.';
        }
    }

    /**
     * Graceful response for greetings, small-talk, or off-topic questions.
     */
    protected function handleUnknown(string $message, array $ctx = []): string
    {
        $language = $ctx['language'] ?? 'en';
        $langInstruction = AiContextBuilder::languageInstruction($language);

        // Dynamically build a list of explicitly permitted modules for the active user session
        $availableModules = [];
        $isOwner = $ctx['user']['is_owner'] ?? false;
        
        foreach (self::MODULE_PERMISSION_MAP as $moduleName => $reqs) {
            $hasPlan = ($reqs['module'] === null || has_module($reqs['module']));
            $hasPerm = ($isOwner || $reqs['permission'] === null || has_permission($reqs['permission']));
            if ($hasPlan && $hasPerm) {
                $availableModules[] = str_replace('_', ' ', $moduleName);
            }
        }
        $availableStr = empty($availableModules) ? 'their account settings' : implode(', ', array_unique($availableModules));

        $systemPrompt = <<<PROMPT
        You are Plantiq AI, an intelligent business operations assistant developed by Qlinkon Technology.

        The user's message was not understood, is off-topic, or maps to a disabled feature.

        RULES:
        - NEVER mention Google, Gemini, OpenAI, ChatGPT, or LLM providers.
        - Plantiq is an all-in-one business operations and management platform (NOT an ERP).
        - Keep replies short, professional, and user-friendly.
        - Ask the user to rephrase their request.
        - ONLY mention these exact available modules to guide them: {$availableStr}.
        - Never mention modules they do not have access to.

        CRITICAL — LANGUAGE (override all defaults):
        {$langInstruction}
        PROMPT;

        try {
            return trim($this->ai->ask($message, $systemPrompt));
        } catch (Exception $e) {
            return match ($language) {
                'hi'       => "मैं आपकी बात समझ नहीं पाया। कृपया अपना प्रश्न दोबारा पूछें।",
                'gu'       => "હું તમારી વાત સમજી શક્યો નથી. કૃપા કરી તમારો પ્રશ્ન ફરીથી પૂછો.",
                'hinglish' => "Main samajh nahi paya. Kripya apna sawal wapas puche.",
                default    => "I didn't quite catch that. Could you please rephrase your request?",
            };
        }
    }
    private function handleUnsupportedAction(string $message, array $context = []): string
    {
        $language = $context['language'] ?? 'en';
        
        return match ($language) {
            'hi'       => "Plantiq AI के माध्यम से सीधे डेटा बनाना या हटाना अभी उपलब्ध नहीं है। हम जल्द ही स्मार्ट ऑटोमेशन सुविधाएँ ला रहे हैं!",
            'gu'       => "Plantiq AI દ્વારા સીધો ડેટા બનાવવો અથવા કાઢી નાખવો હજી ઉપલબ્ધ નથી. અમે ટૂંક સમયમાં સ્માર્ટ ઑટોમેશન સુવિધાઓ લાવી રહ્યા છીએ!",
            'hinglish' => "Plantiq AI se direct data create ya modify karna abhi available nahi hai. Hum jald hi smart automation features la rahe hain!",
            default    => "Direct data modification through Plantiq AI is not available yet. We are actively upgrading the platform with smarter automation features coming soon!",
        };
    }
}