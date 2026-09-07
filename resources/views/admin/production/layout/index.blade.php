@extends ('layouts.admin')

@section ('title', 'Production Layout')

@section ('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Production Layout</h1>
        <p class="mt-0.5 text-xs font-medium text-gray-400">Sites, zones, and growing spaces</p>
    </div>
@endsection

@push ('styles')
    <style>
        .layout-shell {
            min-height: calc(100vh - 8rem);
        }

        @media (min-width: 1024px) {
            .layout-shell {
                height: calc(100vh - 8rem);
                min-height: 640px;
            }
        }

        .layout-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
        }

        .layout-panel-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .layout-field-label {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .layout-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 9px 11px;
            font-size: 13px;
            color: #111827;
            outline: none;
            transition:
                border-color 150ms ease,
                box-shadow 150ms ease;
        }

        .layout-input:focus {
            border-color: var(--brand-600);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 11%, transparent);
        }

        select.layout-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 34px;
        }

        .layout-icon-btn {
            display: inline-flex;
            width: 30px;
            height: 30px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            transition:
                background 140ms ease,
                color 140ms ease,
                border-color 140ms ease;
        }

        .layout-icon-btn:hover {
            border-color: color-mix(in srgb, var(--brand-600) 25%, #e5e7eb);
            background: var(--color-brand-50);
            color: var(--brand-600);
        }

        .layout-primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 8px;
            background: var(--brand-600);
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 800;
            color: #fff;
            transition:
                opacity 140ms ease,
                transform 80ms ease;
        }

        .layout-primary-btn:hover {
            opacity: 0.92;
        }

        .layout-primary-btn:active {
            transform: scale(0.985);
        }

        .layout-secondary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
            color: #374151;
            transition:
                background 140ms ease,
                color 140ms ease,
                border-color 140ms ease;
        }

        .layout-secondary-btn:hover {
            border-color: color-mix(in srgb, var(--brand-600) 22%, #e5e7eb);
            background: var(--color-brand-50);
            color: var(--brand-600);
        }

        .layout-danger-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 8px;
            border: 1px solid #fecaca;
            background: #fff;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
            color: #dc2626;
        }

        .layout-danger-btn:hover {
            background: #fef2f2;
        }
    </style>
@endpush

@section ('content')
    <div
        class="layout-shell -mx-3 overflow-x-hidden overflow-y-auto bg-gray-50 text-gray-800 sm:mx-0 lg:overflow-hidden lg:rounded-lg"
        x-data="productionLayoutWorkspace()"
       
    >
        <div class="flex h-full flex-col gap-6 p-4 lg:grid lg:grid-cols-[300px_minmax(0,1fr)_360px] lg:gap-0 lg:p-0">
            @include ('admin.production.layout.partials.explorer')
            @include ('admin.production.layout.partials.canvas')
            @include ('admin.production.layout.partials.inspector')
        </div>
    </div>
@endsection

@push ('scripts')
    @php
$workspaceRoutes = [
    'layoutSites'      => route('admin.production.layout.sites'),
    'siteZones'        => route('admin.production.layout.site_zones', ['site' => '__ID__']),
    'siteDirectSpaces' => route('admin.production.layout.site_direct_spaces', ['site' => '__ID__']),
    'zoneChildren'     => route('admin.production.layout.zone_children', ['zone' => '__ID__']),

    'siteStore'        => route('admin.production.sites.store'),
    'siteResource'     => route('admin.production.sites.update', ['site' => '__ID__']),

    'zoneStore'        => route('admin.production.zones.store'),
    'zoneResource'     => route('admin.production.zones.update', ['zone' => '__ID__']),

    'spaceShow'        => route('admin.production.growing_spaces.show', ['growing_space' => '__ID__']),
    'spaceStore'       => route('admin.production.growing_spaces.store'),
    'spaceResource'    => route('admin.production.growing_spaces.update', ['growing_space' => '__ID__']),

    'bulkPreview'      => route('admin.production.growing_spaces.bulk_preview'),
    'bulkGenerate'     => route('admin.production.growing_spaces.bulk_generate'),
    'typeStore'        => route('admin.production.growing_space_types.store'),
    'typeResource'     => route('admin.production.growing_space_types.update', ['growing_space_type' => '__ID__']),

    'siteOccupancy'      => route('admin.production.sites.occupancy', ['site' => '__ID__']),
    'zoneOccupancy'      => route('admin.production.zones.occupancy', ['zone' => '__ID__']),
    'spaceOccupancy'     => route('admin.production.growing-spaces.occupancy', ['growingSpace' => '__ID__']),
    'spacesOccupancyMap' => route('admin.production.growing-spaces.occupancy_map'),
    'batchRelease'       => route('admin.production.plant-batches.release', ['plantBatch' => '__ID__']),
    'batchPlace'         => route('admin.production.plant-batches.place', ['plantBatch' => '__ID__']),
    'activeBatches'      => route('admin.production.plant-batches.active'),
];
@endphp
    <script>
        window.productionLayoutWorkspace = function () {
            return {
                routes: @js ($workspaceRoutes),
                growingSpaceTypes: @json ($growingSpaceTypes->values()),
                capacityUnitOptions: [
                    { id: 'pots', name: 'Pots (count)' },
                    { id: 'area_sqm', name: 'Area (sq. m)' },
                    { id: 'linear_m', name: 'Length (linear m)' },
                    { id: 'tray_cells', name: 'Tray Cells (count)' },
                    { id: 'custom', name: 'Custom' }
                ],
                typeForm: { name: "", capacity_unit: "pots", custom_unit_label: "" },
                typeSaving: false,
                nodes: [],
                activeNode: null,
                inspectorMode: "details",
                search: "",
                loading: false,
                saving: false,
                errorMessage: "",
                occupancy: null,
                occupancyLoading: false,
                capacityBreakdownOpen: false,
                siteForm: {},
                zoneForm: {},
                spaceForm: {},
                bulkForm: {},
                bulkPreview: [],
                bulkConflictCount: 0,
                bulkPreviewReady: false,
                occupancy: { batch_count: 0, quantity_occupied: 0, is_over_capacity: false, batches: [] },
                occupancyLoading: false,

                // ── Placement Modal state ──────────────────────────────────
                placementModal: {
                    open: false,
                    loading: false,
                    saving: false,
                    search: "",
                    batches: [], // fetched active batches list
                    selectedBatchId: null,
                    notes: "",
                },

                init() {
                    this.resetForms();
                    this.loadSites();
                },

                resetForms() {
                    this.siteForm = {
                        name: "",
                        is_active: true,
                        sort_order: 0,
                    };
                    this.zoneForm = {
                        production_site_id: null,
                        parent_id: null,
                        name: "",
                        is_active: true,
                        sort_order: 0,
                    };
                    this.spaceForm = {
                        production_site_id: null,
                        zone_id: null,
                        growing_space_type_id: this.growingSpaceTypes[0]?.id ?? "",
                        name: "",
                        capacity: "",
                        is_active: true,
                        sort_order: 0,
                    };
                    this.bulkForm = {
                        production_site_id: null,
                        zone_id: null,
                        growing_space_type_id: this.growingSpaceTypes[0]?.id ?? "",
                        capacity: "",
                        prefix: "",
                        row_start: 1,
                        row_end: 1,
                        col_start: 1,
                        col_end: 1,
                        name_template: "{prefix}R{row}-C{col}",
                        sort_order_start: 0,
                        skip_conflicts: false,
                    };
                    this.bulkPreview = [];
                    this.bulkConflictCount = 0;
                    this.bulkPreviewReady = false;
                },

                url(template, id) {
                    return template.replace("__ID__", encodeURIComponent(id));
                },

                csrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
                },

                async request(url, options = {}) {
                    const method = options.method || "GET";
                    const body = options.body ?? null;
                    const headers = {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    };

                    if (method !== "GET") {
                        headers["X-CSRF-TOKEN"] = this.csrf();
                    }

                    if (body !== null) {
                        headers["Content-Type"] = "application/json";
                    }

                    const response = await fetch(url, {
                        method,
                        headers,
                        body: body === null ? null : JSON.stringify(body),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || data.success === false) {
                        const message = data.message || "Request failed. Please try again.";
                        throw new Error(message);
                    }

                    return data;
                },

                notify(message, type = "success") {
                    if (window.BizAlert?.toast) {
                        window.BizAlert.toast(message, type);
                        return;
                    }

                    if (window.Swal) {
                        Swal.fire({
                            toast: true,
                            position: "top-end",
                            icon: type,
                            title: message,
                            showConfirmButton: false,
                            timer: 2200,
                        });
                    }
                },

                async confirm(title, text, confirmButtonText = "Confirm") {
                    if (window.BizAlert?.confirm) {
                        return await window.BizAlert.confirm(title, text, confirmButtonText);
                    }

                    if (!window.Swal) {
                        return { isConfirmed: window.confirm(text) };
                    }

                    return await Swal.fire({
                        title,
                        text,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText,
                        confirmButtonColor: "#dc2626",
                    });
                },

                decorateNode(node, context = {}) {
                    return {
                        ...node,
                        key: `${node.type}:${node.id}`,
                        production_site_id:
                            context.production_site_id ?? node.production_site_id ?? (node.type === "site" ? node.id : null),
                        parent_id: context.parent_id ?? node.parent_id ?? null,
                        zone_id: context.zone_id ?? node.zone_id ?? null,
                        depth: context.depth ?? 0,
                        path: context.path ?? [node.name],
                        expanded: false,
                        loading: false,
                        childrenLoaded: false,
                        children: [],
                    };
                },

                async loadSites() {
                    this.loading = true;
                    this.errorMessage = "";
                    this.activeNode = null; // FIX: Ensure Canvas & Explorer reset to root state on refresh

                    try {
                        const data = await this.request(this.routes.layoutSites);
                        this.nodes = (data.nodes || []).map((node) =>
                            this.decorateNode(node, {
                                depth: 0,
                                path: [node.name],
                            }),
                        );
                    } catch (error) {
                        this.errorMessage = error.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async refreshTree() {
                    const selected = this.activeNode ? { type: this.activeNode.type, id: this.activeNode.id } : null;
                    await this.loadSites();
                    if (selected) {
                        this.activeNode = null;
                    }
                },

                async toggleNode(node) {
                    if (!this.hasChildren(node)) {
                        return;
                    }

                    if (!node.childrenLoaded) {
                        await this.loadChildren(node);
                    }

                    node.expanded = !node.expanded;
                },

                // Helper to ensure all parent folders stay open in the Explorer
                expandAncestorsOf(targetNode) {
                    if (!targetNode) return;
                    const walk = (items) => {
                        let found = false;
                        for (let i = 0; i < (items || []).length; i++) {
                            const n = items[i];
                            if (n.key === targetNode.key) return true;
                            if (n.children && n.children.length > 0) {
                                if (walk(n.children)) {
                                    n.expanded = true; // Force Open the parent folder
                                    found = true;
                                }
                            }
                        }
                        return found;
                    };
                    walk(this.nodes);
                },

                async selectNode(node) {
                    // Update active node early for instant UI response
                    this.activeNode = node;
                    this.inspectorMode = "details";
                    this.bulkPreviewReady = false;
                    this.bulkPreview = [];
                    this.errorMessage = "";
                    this.occupancy = {
                        batch_count: node.occupancy?.batch_count || 0,
                        quantity_occupied: node.occupancy?.quantity_occupied || 0,
                        is_over_capacity: node.occupancy?.is_over_capacity || false,
                        batches: [],
                    };

                    if (node.type === "site" || node.type === "zone") {
                        if (!node.childrenLoaded) {
                            await this.loadChildren(node);
                        }
                        // FIX: Moved OUTSIDE the if-block so it always expands even if already loaded
                        node.expanded = true;

                        this.loadOccupancy(node);
                        this.loadChildOccupancyBadges(node);
                    }

                    if (node.type === "growing_space") {
                        await this.loadSpaceDetails(node);
                    }

                    this.fillDetailsForm();

                    // FIX: Ensure parent folders expand so this deep node is visible in Explorer
                    this.expandAncestorsOf(node);

                    // FIX: Force Alpine to redraw the tree after deep async mutations
                    this.nodes = [...this.nodes];
                },

                async loadChildren(node) {
                    if (node.type === "growing_space") {
                        return;
                    }

                    node.loading = true;

                    try {
                        let children = [];

                        if (node.type === "site") {
                            const [zones, spaces] = await Promise.all([
                                this.request(this.url(this.routes.siteZones, node.id)),
                                this.request(this.url(this.routes.siteDirectSpaces, node.id)),
                            ]);

                            children = [
                                ...(zones.nodes || []).map((child) =>
                                    this.decorateNode(child, {
                                        production_site_id: node.id,
                                        parent_id: null,
                                        depth: node.depth + 1,
                                        path: [...node.path, child.name],
                                    }),
                                ),
                                ...(spaces.nodes || []).map((child) =>
                                    this.decorateNode(child, {
                                        production_site_id: node.id,
                                        zone_id: null,
                                        depth: node.depth + 1,
                                        path: [...node.path, child.name],
                                    }),
                                ),
                            ];
                        }

                        if (node.type === "zone") {
                            const data = await this.request(this.url(this.routes.zoneChildren, node.id));
                            children = (data.nodes || []).map((child) =>
                                this.decorateNode(child, {
                                    production_site_id: node.production_site_id,
                                    parent_id: child.type === "zone" ? node.id : null,
                                    zone_id: child.type === "growing_space" ? node.id : null,
                                    depth: node.depth + 1,
                                    path: [...node.path, child.name],
                                }),
                            );
                        }

                        node.children = children.sort(
                            (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0) || a.name.localeCompare(b.name),
                        );
                        node.childrenLoaded = true;
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        node.loading = false;
                    }
                },

                async loadSpaceDetails(node) {
                    node.loading = true;
                    this.occupancyLoading = true;

                    try {
                        const data = await this.request(this.url(this.routes.spaceShow, node.id));
                        Object.assign(node, data.space || {});
                        node.growing_space_type_id = data.space?.growing_space_type_id ?? node.growing_space_type_id;
                        node.occupancy = {
                            batch_count: data.occupancy?.batch_count ?? 0,
                            quantity_occupied: data.occupancy?.quantity_occupied ?? 0,
                        };
                        this.occupancy = {
                            batch_count: data.occupancy?.batch_count ?? 0,
                            quantity_occupied: data.occupancy?.quantity_occupied ?? 0,
                            is_over_capacity: data.occupancy?.is_over_capacity ?? false,
                            batches: data.occupancy?.batches ?? [],
                        };
                        this.activeNode = node;
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        node.loading = false;
                        this.occupancyLoading = false;
                    }
                },

                async loadOccupancy(node) {
                    this.occupancyLoading = true;
                    this.capacityBreakdownOpen = false;

                    try {
                        const url =
                            node.type === "site"
                                ? this.url(this.routes.siteOccupancy, node.id)
                                : this.url(this.routes.zoneOccupancy, node.id);
                        const data = await this.request(url);
                        this.occupancy = data.occupancy;
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        this.occupancyLoading = false;
                    }
                },

                async loadLeafOccupancy(node) {
                    this.occupancyLoading = true;

                    try {
                        const data = await this.request(this.url(this.routes.spaceOccupancy, node.id));
                        this.occupancy = data.summary;
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        this.occupancyLoading = false;
                    }
                },

                async loadChildOccupancyBadges(node) {
                    const leafIds = (node.children || [])
                        .filter((child) => child.type === "growing_space")
                        .map((child) => child.id);

                    if (!leafIds.length) {
                        return;
                    }

                    try {
                        const qs = leafIds.map((id) => `ids[]=${id}`).join("&");
                        const data = await this.request(`${this.routes.spacesOccupancyMap}?${qs}`);
                        const map = data.occupancy || {};

                        node.children = node.children.map((child) =>
                            child.type === "growing_space" && map[child.id] ? { ...child, occupancy: map[child.id] } : child,
                        );
                    } catch (error) {
                        // Badges are a nice-to-have — a failed fetch shouldn't block the grid.
                    }
                },

                // ── Open the "Place a Batch" modal for a growing space ─────
                async openPlacementModal() {
                    this.placementModal.open = true;
                    this.placementModal.search = "";
                    this.placementModal.selectedBatchId = null;
                    this.placementModal.notes = "";
                    this.placementModal.batches = [];
                    this.placementModal.loading = true;

                    try {
                        const data = await this.request(this.routes.activeBatches);
                        this.placementModal.batches = data.batches || [];
                    } catch (error) {
                        this.notify(error.message, "error");
                        this.placementModal.open = false;
                    } finally {
                        this.placementModal.loading = false;
                    }
                },

                // ── Live-search active batches ─────────────────────────────
                async searchBatches() {
                    this.placementModal.loading = true;
                    try {
                        const qs = this.placementModal.search ? `?q=${encodeURIComponent(this.placementModal.search)}` : "";
                        const data = await this.request(`${this.routes.activeBatches}${qs}`);
                        this.placementModal.batches = data.batches || [];
                    } catch (error) {
                        // Silent — stale list is still shown
                    } finally {
                        this.placementModal.loading = false;
                    }
                },

                // ── Confirm and POST the placement ─────────────────────────
                async placeBatch() {
                    if (!this.placementModal.selectedBatchId || !this.activeNode) return;

                    this.placementModal.saving = true;
                    try {
                        const url = this.url(this.routes.batchPlace, this.placementModal.selectedBatchId);
                        const data = await this.request(url, {
                            method: "POST",
                            body: {
                                growing_space_id: this.activeNode.id,
                                notes: this.placementModal.notes || null,
                            },
                        });

                        this.notify(data.message || "Batch placed successfully.");
                        this.placementModal.open = false;

                        // Refresh occupancy so canvas shows the new batch immediately
                        await this.loadSpaceDetails(this.activeNode);
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        this.placementModal.saving = false;
                    }
                },

                // ── Filtered batches list inside modal ─────────────────────
                get filteredModalBatches() {
                    const term = this.placementModal.search.trim().toLowerCase();
                    if (!term) return this.placementModal.batches;
                    return this.placementModal.batches.filter(
                        (b) => b.batch_code.toLowerCase().includes(term) || (b.product_name || "").toLowerCase().includes(term),
                    );
                },

                async releaseBatch(batchId, batchCode) {
                    const confirmed = await this.confirm(
                        `Release ${batchCode}`,
                        "Remove this batch from its current space?",
                        "Release",
                    );

                    if (!confirmed.isConfirmed) {
                        return;
                    }

                    try {
                        const data = await this.request(this.url(this.routes.batchRelease, batchId), { method: "DELETE" });
                        this.notify(data.message || "Batch released.");

                        if (this.activeNode) {
                            // Fix: Use loadSpaceDetails instead of loadLeafOccupancy
                            // to ensure the 'batches' array is fully re-fetched and rendered.
                            await this.loadSpaceDetails(this.activeNode);
                        }
                    } catch (error) {
                        this.notify(error.message, "error");
                    }
                },

                fillDetailsForm() {
                    if (!this.activeNode) {
                        this.resetForms();
                        return;
                    }

                    if (this.activeNode.type === "site") {
                        this.siteForm = {
                            name: this.activeNode.name || "",
                            is_active: Boolean(this.activeNode.is_active),
                            sort_order: this.activeNode.sort_order ?? 0,
                        };
                    }

                    if (this.activeNode.type === "zone") {
                        this.zoneForm = {
                            production_site_id: this.activeNode.production_site_id,
                            parent_id: this.activeNode.parent_id,
                            name: this.activeNode.name || "",
                            is_active: Boolean(this.activeNode.is_active),
                            sort_order: this.activeNode.sort_order ?? 0,
                        };
                    }

                    if (this.activeNode.type === "growing_space") {
                        this.spaceForm = {
                            production_site_id: this.activeNode.production_site_id,
                            zone_id: this.activeNode.zone_id,
                            growing_space_type_id: this.activeNode.growing_space_type_id || "",
                            name: this.activeNode.name || "",
                            capacity: this.activeNode.capacity || "",
                            is_active: Boolean(this.activeNode.is_active),
                            sort_order: this.activeNode.sort_order ?? 0,
                        };
                    }
                },

                prepareCreateSite() {
                    this.inspectorMode = "create-site";
                    this.siteForm = {
                        name: "",
                        is_active: true,
                        sort_order: 0,
                    };
                },

                prepareCreateZone() {
                    const context = this.currentContext();
                    if (!context) {
                        this.notify("Select a production site or zone first.", "error");
                        return;
                    }

                    this.inspectorMode = "create-zone";
                    this.zoneForm = {
                        production_site_id: context.production_site_id,
                        parent_id: context.parent_id,
                        name: "",
                        is_active: true,
                        sort_order: 0,
                    };
                },

                prepareCreateSpace() {
                    const context = this.currentContext();
                    if (!context) {
                        this.notify("Select a production site or zone first.", "error");
                        return;
                    }

                    this.inspectorMode = "create-space";
                    this.spaceForm = {
                        production_site_id: context.production_site_id,
                        zone_id: context.zone_id,
                        growing_space_type_id: this.growingSpaceTypes[0]?.id ?? "",
                        name: "",
                        capacity: "",
                        is_active: true,
                        sort_order: 0,
                    };
                },

                prepareBulk() {
                    const context = this.currentContext();
                    if (!context) {
                        this.notify("Select a production site or zone first.", "error");
                        return;
                    }

                    this.inspectorMode = "bulk";
                    this.bulkForm = {
                        production_site_id: context.production_site_id,
                        zone_id: context.zone_id,
                        growing_space_type_id: this.growingSpaceTypes[0]?.id ?? "",
                        capacity: "",
                        prefix: "",
                        row_start: 1,
                        row_end: 1,
                        col_start: 1,
                        col_end: 1,
                        name_template: "{prefix}R{row}-C{col}",
                        sort_order_start: 0,
                        skip_conflicts: false,
                    };
                    this.bulkPreview = [];
                    this.bulkConflictCount = 0;
                    this.bulkPreviewReady = false;
                },

                currentContext() {
                    if (!this.activeNode || this.activeNode.type === "growing_space") {
                        return null;
                    }

                    return {
                        production_site_id:
                            this.activeNode.type === "site" ? this.activeNode.id : this.activeNode.production_site_id,
                        parent_id: this.activeNode.type === "zone" ? this.activeNode.id : null,
                        zone_id: this.activeNode.type === "zone" ? this.activeNode.id : null,
                    };
                },

                async saveDetails() {
                    if (!this.activeNode) {
                        return;
                    }

                    if (this.activeNode.type === "site") {
                        await this.updateNode(
                            this.url(this.routes.siteResource, this.activeNode.id),
                            this.sitePayload(),
                            "PUT",
                            "site",
                        );
                    }

                    if (this.activeNode.type === "zone") {
                        await this.updateNode(
                            this.url(this.routes.zoneResource, this.activeNode.id),
                            this.zonePayload(),
                            "PUT",
                            "zone",
                        );
                    }

                    if (this.activeNode.type === "growing_space") {
                        await this.updateNode(
                            this.url(this.routes.spaceResource, this.activeNode.id),
                            this.spacePayload(),
                            "PUT",
                            "space",
                        );
                    }
                },

                async createSite() {
                    await this.createNode(this.routes.siteStore, this.sitePayload(), "site");
                },

                async createZone() {
                    await this.createNode(this.routes.zoneStore, this.zonePayload(), "zone");
                },

                async createSpace() {
                    await this.createNode(this.routes.spaceStore, this.spacePayload(), "space");
                },

                sitePayload() {
                    return {
                        name: this.siteForm.name,
                        is_active: Boolean(this.siteForm.is_active),
                        sort_order: Number(this.siteForm.sort_order || 0),
                    };
                },

                zonePayload() {
                    return {
                        production_site_id: Number(this.zoneForm.production_site_id),
                        parent_id: this.zoneForm.parent_id ? Number(this.zoneForm.parent_id) : null,
                        name: this.zoneForm.name,
                        is_active: Boolean(this.zoneForm.is_active),
                        sort_order: Number(this.zoneForm.sort_order || 0),
                    };
                },

                spacePayload() {
                    return {
                        production_site_id: Number(this.spaceForm.production_site_id),
                        zone_id: this.spaceForm.zone_id ? Number(this.spaceForm.zone_id) : null,
                        growing_space_type_id: Number(this.spaceForm.growing_space_type_id),
                        name: this.spaceForm.name,
                        capacity: Number(this.spaceForm.capacity),
                        is_active: Boolean(this.spaceForm.is_active),
                        sort_order: Number(this.spaceForm.sort_order || 0),
                    };
                },

                async updateNode(url, payload, method, responseKey) {
                    this.saving = true;

                    try {
                        const data = await this.request(url, { method, body: payload });
                        const updated = data[responseKey] || payload;
                        Object.assign(this.activeNode, {
                            ...updated,
                            type: this.activeNode.type,
                            key: this.activeNode.key,
                            children: this.activeNode.children,
                            childrenLoaded: this.activeNode.childrenLoaded,
                            expanded: this.activeNode.expanded,
                            path: [...this.activeNode.path.slice(0, -1), updated.name || payload.name],
                        });
                        this.fillDetailsForm();
                        this.notify(data.message || "Saved successfully.");
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        this.saving = false;
                    }
                },

                async createNode(url, payload, responseKey) {
                    this.saving = true;

                    try {
                        const data = await this.request(url, { method: "POST", body: payload });
                        const created = data[responseKey];
                        const type = responseKey === "space" ? "growing_space" : responseKey;
                        const parent = this.activeNode && this.activeNode.type !== "growing_space" ? this.activeNode : null;
                        const node = this.decorateNode(
                            {
                                ...created,
                                type,
                                children_summary: {
                                    has_zones: false,
                                    has_spaces: false,
                                    total_children: 0,
                                },
                            },
                            {
                                production_site_id: created.production_site_id ?? created.id,
                                parent_id: type === "zone" ? created.parent_id : null,
                                zone_id: type === "growing_space" ? created.zone_id : null,
                                depth: parent ? parent.depth + 1 : 0,
                                path: parent ? [...parent.path, created.name] : [created.name],
                            },
                        );

                        if (type === "site") {
                            this.nodes = [...this.nodes, node].sort(
                                (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0) || a.name.localeCompare(b.name),
                            );
                        } else if (parent) {
                            parent.childrenLoaded = true;
                            parent.expanded = true;
                            parent.children = [...(parent.children || []), node].sort(
                                (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0) || a.name.localeCompare(b.name),
                            );
                            parent.children_summary = parent.children_summary || {};
                            parent.children_summary.total_children = parent.children.length;
                            parent.children_summary.has_zones = parent.children.some((child) => child.type === "zone");
                            parent.children_summary.has_spaces = parent.children.some(
                                (child) => child.type === "growing_space",
                            );
                        }

                        await this.selectNode(node);
                        this.notify(data.message || "Created successfully.");
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        this.saving = false;
                    }
                },

                async deleteActiveNode() {
                    if (!this.activeNode) {
                        return;
                    }

                    const confirmed = await this.confirm(
                        `Delete ${this.typeLabel(this.activeNode.type)}`,
                        `Delete "${this.activeNode.name}"? This cannot be undone.`,
                        "Delete",
                    );

                    if (!confirmed.isConfirmed) {
                        return;
                    }

                    const node = this.activeNode;
                    const route =
                        node.type === "site"
                            ? this.url(this.routes.siteResource, node.id)
                            : node.type === "zone"
                              ? this.url(this.routes.zoneResource, node.id)
                              : this.url(this.routes.spaceResource, node.id);

                    this.saving = true;

                    try {
                        const data = await this.request(route, { method: "DELETE" });
                        this.removeNode(node);
                        this.activeNode = null;
                        this.inspectorMode = "details";
                        this.notify(data.message || "Deleted successfully.");
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        this.saving = false;
                    }
                },

                removeNode(target) {
                    const removeFrom = (nodes) => {
                        const index = nodes.findIndex((node) => node.key === target.key);
                        if (index >= 0) {
                            nodes.splice(index, 1);
                            return true;
                        }
                        return nodes.some((node) => removeFrom(node.children || []));
                    };

                    removeFrom(this.nodes);
                    this.nodes = [...this.nodes]; // Explicit deep refresh for Alpine proxy engine
                    if (this.activeNode && this.activeNode.key === target.key) {
                        this.activeNode = null;
                    }
                },

                bulkPayload() {
                    return {
                        production_site_id: Number(this.bulkForm.production_site_id),
                        zone_id: this.bulkForm.zone_id ? Number(this.bulkForm.zone_id) : null,
                        growing_space_type_id: Number(this.bulkForm.growing_space_type_id),
                        capacity: Number(this.bulkForm.capacity),
                        prefix: this.bulkForm.prefix || "",
                        row_start: Number(this.bulkForm.row_start),
                        row_end: Number(this.bulkForm.row_end),
                        col_start: Number(this.bulkForm.col_start),
                        col_end: Number(this.bulkForm.col_end),
                        name_template: this.bulkForm.name_template || null,
                        sort_order_start: Number(this.bulkForm.sort_order_start || 0),
                        skip_conflicts: Boolean(this.bulkForm.skip_conflicts),
                    };
                },

                async previewBulk() {
                    this.saving = true;

                    try {
                        const data = await this.request(this.routes.bulkPreview, {
                            method: "POST",
                            body: this.bulkPayload(),
                        });
                        this.bulkPreview = data.grid || [];
                        this.bulkConflictCount = data.conflict_count || 0;
                        this.bulkPreviewReady = true;
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        this.saving = false;
                    }
                },

                async saveNewType() {
                    this.typeSaving = true;
                    try {
                        const data = await this.request(this.routes.typeStore, {
                            method: "POST",
                            body: {
                                name: this.typeForm.name,
                                capacity_unit: this.typeForm.capacity_unit,
                                custom_unit_label:
                                    this.typeForm.capacity_unit === "custom" ? this.typeForm.custom_unit_label : null,
                            },
                        });
                        this.growingSpaceTypes = [...this.growingSpaceTypes, data.type];
                        this.typeForm = { name: "", capacity_unit: "pots", custom_unit_label: "" };
                        this.notify(data.message || "Type created.");
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        this.typeSaving = false;
                    }
                },

                async deleteType(type) {
                    const confirmed = await this.confirm(
                        `Delete type "${type.name}"?`,
                        "Growing Spaces using this type must be reassigned first.",
                        "Delete",
                    );
                    if (!confirmed.isConfirmed) return;
                    try {
                        const data = await this.request(this.url(this.routes.typeResource, type.id), { method: "DELETE" });
                        this.growingSpaceTypes = this.growingSpaceTypes.filter((t) => t.id !== type.id);
                        this.notify(data.message || "Type deleted.");
                    } catch (error) {
                        this.notify(error.message, "error");
                    }
                },

                async generateBulk() {
                    this.saving = true;

                    try {
                        const data = await this.request(this.routes.bulkGenerate, {
                            method: "POST",
                            body: this.bulkPayload(),
                        });

                        if (this.activeNode && this.activeNode.type !== "growing_space") {
                            this.activeNode.childrenLoaded = false;
                            await this.loadChildren(this.activeNode);
                            this.activeNode.expanded = true;
                        }

                        this.bulkPreviewReady = false;
                        this.bulkPreview = [];
                        this.notify(data.message || "Growing spaces generated.");
                    } catch (error) {
                        this.notify(error.message, "error");
                    } finally {
                        this.saving = false;
                    }
                },

                hasChildren(node) {
                    if (node.type === "growing_space") {
                        return false;
                    }

                    if (!node.childrenLoaded) {
                        return (node.children_summary?.total_children || 0) > 0;
                    }

                    return (node.children || []).length > 0;
                },

                get activeChildren() {
                    return this.activeNode && this.activeNode.type !== "growing_space"
                        ? this.activeNode.children || []
                        : this.nodes;
                },

                get selectedPath() {
                    return this.activeNode?.path?.join(" / ") || "All production sites";
                },

                get treeRows() {
                    const rows = [];
                    const term = this.search.trim().toLowerCase();

                    const includeNode = (node) => {
                        if (!term) {
                            return true;
                        }

                        return (
                            node.name.toLowerCase().includes(term) ||
                            this.typeLabel(node.type).toLowerCase().includes(term) ||
                            (node.children || []).some(includeNode)
                        );
                    };

                    const walk = (nodes, depth) => {
                        nodes.forEach((node) => {
                            if (!includeNode(node)) {
                                return;
                            }

                            rows.push({ node, depth });

                            if ((node.expanded || term) && node.children?.length) {
                                walk(node.children, depth + 1);
                            }
                        });
                    };

                    walk(this.nodes, 0);
                    return rows;
                },

                typeLabel(type) {
                    return (
                        {
                            site: "Production Site",
                            zone: "Zone",
                            growing_space: "Growing Space",
                        }[type] || type
                    );
                },

                typeIcon(type) {
                    return (
                        {
                            site: "fa-industry",
                            zone: "fa-layer-group",
                            growing_space: "fa-seedling",
                        }[type] || "fa-circle"
                    );
                },

                typeTone(type) {
                    return (
                        {
                            site: "text-gray-700 bg-gray-100",
                            zone: "text-blue-700 bg-blue-50",
                            growing_space: "text-emerald-700 bg-emerald-50",
                        }[type] || "text-gray-700 bg-gray-100"
                    );
                },

                occupancyBadgeClass(node) {
                    const capacity = Number(node.capacity || 0);
                    const occupied = Number(node.occupancy?.quantity_occupied || 0);

                    if (capacity > 0 && occupied > capacity) {
                        return "bg-red-50 text-red-600";
                    }

                    return "bg-blue-50 text-blue-700";
                },

                capacityUnit(typeId) {
                    const type = this.growingSpaceTypes.find((item) => Number(item.id) === Number(typeId));
                    if (!type) {
                        return "";
                    }

                    if (type.capacity_unit === "custom") {
                        return type.custom_unit_label || "Custom";
                    }

                    return (
                        {
                            pots: "Pots",
                            area_sqm: "sq. m",
                            linear_m: "linear m",
                            tray_cells: "Tray cells",
                        }[type.capacity_unit] || type.capacity_unit
                    );
                },

                isCountBasedType(typeId) {
                    const type = this.growingSpaceTypes.find((item) => Number(item.id) === Number(typeId));
                    return type ? ["pots", "tray_cells"].includes(type.capacity_unit) : false;
                },

                occupancyPercentage(occupied, capacity) {
                    const occ = Number(occupied) || 0;
                    const cap = Number(capacity) || 0;
                    if (cap === 0) return 0;
                    return Math.round((occ / cap) * 100);
                },

                occupancyBarWidth(occupied, capacity) {
                    return Math.min(100, this.occupancyPercentage(occupied, capacity)) + "%";
                },

                occupancyFillColor(pct) {
                    if (pct === null || pct === undefined) return "bg-gray-300";
                    if (pct >= 100) return "bg-red-500";
                    if (pct >= 70) return "bg-amber-500";
                    return "bg-emerald-500";
                },

                formatCapacityUnit(unit) {
                    const maps = { pots: "Pots", area_sqm: "Sq.m", linear_m: "Linear m", tray_cells: "Tray Cells" };
                    return maps[unit] || unit;
                },

                spaceTypeName(typeId) {
                    const type = this.growingSpaceTypes.find((item) => Number(item.id) === Number(typeId));
                    return type?.name || "Growing space";
                },

                selectedClass(node) {
                    return this.activeNode?.key === node.key
                        ? "bg-[var(--brand-600)] text-white border-[var(--brand-600)]"
                        : "bg-white text-gray-700 border-gray-100 hover:border-brand-100 hover:bg-brand-50";
                },

                // Explorer row styling — same active/inactive logic as selectedClass(),
                // kept separate so the tree's row chrome (padding, shadow) can evolve
                // independently from other selectable surfaces in the workspace.
                explorerRowClass(node) {
                    return this.activeNode?.key === node.key
                        ? "bg-[var(--brand-600)] text-white"
                        : "text-gray-700 hover:bg-gray-50";
                },

                // Sub-label under each row name — richer than a bare typeLabel().
                // Sites show total children, zones show a sub-zone/space split,
                // growing spaces show their type name (e.g. "Raised Bench").
                explorerSubLabel(node) {
                    if (node.type === "growing_space") {
                        return this.capacityUnit(node.growing_space_type_id)
                            ? this.spaceTypeName(node.growing_space_type_id)
                            : "Growing space";
                    }

                    if (node.type === "zone") {
                        const total = node.children_summary?.total_children || 0;
                        return total ? `Zone \u00b7 ${total} ${total === 1 ? "item" : "items"}` : "Zone";
                    }

                    // site
                    const total = node.children_summary?.total_children || 0;
                    return total ? `Production site \u00b7 ${total} ${total === 1 ? "item" : "items"}` : "Production site";
                },

                // Growing Space Type's own name (e.g. "Raised Bench"), not the capacity unit.
                spaceTypeName(typeId) {
                    const type = this.growingSpaceTypes.find((item) => Number(item.id) === Number(typeId));
                    return type?.name || "Growing space";
                },

                // Occupancy dot color for a growing_space row.
                // Grey  = no batches placed here yet
                // Green = occupied, within capacity
                // Red   = occupied quantity exceeds capacity
                occupancyDotClass(node) {
                    const occupied = Number(node.occupancy?.quantity_occupied || 0);
                    const capacity = Number(node.capacity || 0);

                    if (occupied === 0) {
                        return "bg-gray-300";
                    }

                    if (capacity > 0 && occupied > capacity) {
                        return "bg-red-500";
                    }

                    return "bg-emerald-500";
                },

                occupancyDotTitle(node) {
                    const occupied = Number(node.occupancy?.quantity_occupied || 0);
                    const batches = Number(node.occupancy?.batch_count || 0);

                    if (occupied === 0) {
                        return "Empty \u2014 no batches placed";
                    }

                    return `${occupied} occupied across ${batches} ${batches === 1 ? "batch" : "batches"}`;
                },
            };
        };
    </script>
@endpush
