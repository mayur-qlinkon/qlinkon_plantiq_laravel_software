<aside
    class="layout-panel order-3 flex min-h-[400px] flex-col overflow-hidden rounded-lg shadow-sm lg:order-none lg:min-h-0 lg:rounded-none lg:rounded-r-lg lg:border-y-0 lg:border-l lg:border-r-0 lg:shadow-none">
    <div class="flex h-12 items-center justify-between border-b border-gray-100 px-4">
        <div class="flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                <i class="fa-solid fa-sliders text-xs"></i>
            </span>
            <span class="layout-panel-title">Inspector</span>
        </div>

        <div class="flex items-center gap-1">
            <button type="button" class="layout-icon-btn" title="Manage space types"
                @click="inspectorMode = inspectorMode === 'types' ? 'details' : 'types'">
                <i class="fa-solid fa-tags text-xs"></i>
            </button>
            <button type="button" class="layout-icon-btn" title="Create site" @click="prepareCreateSite()">
                <i class="fa-solid fa-plus text-xs"></i>
            </button>
        </div>
    </div>

    <div class="nav-scroll min-h-0 flex-1 overflow-y-auto p-4">
        <template x-if="!activeNode && inspectorMode === 'details'">
            <div class="flex h-full min-h-[460px] flex-col items-center justify-center text-center">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                    <i class="fa-solid fa-arrow-pointer"></i>
                </div>
                <p class="text-sm font-black text-gray-700">No selection</p>
                <p class="mt-1 max-w-xs text-xs leading-5 text-gray-400">Select a node in Explorer to edit it, or create
                    a production site.</p>
                <button type="button" class="layout-primary-btn mt-4" @click="prepareCreateSite()">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Create Site
                </button>
            </div>
        </template>

        <template x-if="activeNode && inspectorMode === 'details'">
            <div class="space-y-5">
                <div>
                    <p class="layout-panel-title">Selected Node</p>
                    <div class="mt-3 flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                            :class="typeTone(activeNode.type)">
                            <i class="fa-solid text-xs" :class="typeIcon(activeNode.type)"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-black text-gray-900" x-text="activeNode.name"></p>
                            <p class="mt-0.5 text-xs font-semibold text-gray-500"
                                x-text="
                                    typeLabel(activeNode.type)
                                ">
                            </p>
                        </div>
                    </div>
                </div>

                <form class="space-y-4" @submit.prevent="saveDetails()">
                    <template x-if="activeNode.type === 'site'">
                        <div class="space-y-4">
                            <div>
                                <label class="layout-field-label">Site Name</label>
                                <input type="text" class="layout-input" x-model="siteForm.name" required />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="layout-field-label">Sort Order</label>
                                    <input type="number" min="0" class="layout-input"
                                        x-model.number="siteForm.sort_order" />
                                </div>
                                <label class="flex items-end">
                                    <span
                                        class="flex h-[39px] w-full items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-600">
                                        <input type="checkbox"
                                            class="text-brand-600 focus:ring-brand-500 rounded border-gray-300"
                                            x-model="siteForm.is_active" />
                                        Active
                                    </span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeNode.type === 'zone'">
                        <div class="space-y-4">
                            <div>
                                <label class="layout-field-label">Zone Name</label>
                                <input type="text" class="layout-input" x-model="zoneForm.name" required />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="layout-field-label">Sort Order</label>
                                    <input type="number" min="0" class="layout-input"
                                        x-model.number="zoneForm.sort_order" />
                                </div>
                                <label class="flex items-end">
                                    <span
                                        class="flex h-[39px] w-full items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-600">
                                        <input type="checkbox"
                                            class="text-brand-600 focus:ring-brand-500 rounded border-gray-300"
                                            x-model="zoneForm.is_active" />
                                        Active
                                    </span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeNode.type === 'growing_space'">
                        <div class="space-y-4">
                            <div>
                                <label class="layout-field-label">Growing Space Name</label>
                                <input type="text" class="layout-input" x-model="spaceForm.name" required />
                            </div>
                            <div>
                                <label class="layout-field-label">Growing Space Type</label>
                                <x-alpine-select name="growing_space_type_id" model="spaceForm.growing_space_type_id"
                                    items="growingSpaceTypes" :required="true" :allow-empty="false" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="layout-field-label">Capacity</label>
                                    <input type="number" min="0.01" step="0.01" class="layout-input"
                                        x-model="spaceForm.capacity" required />
                                </div>
                                <div>
                                    <label class="layout-field-label">Sort Order</label>
                                    <input type="number" min="0" class="layout-input"
                                        x-model.number="spaceForm.sort_order" />
                                </div>
                            </div>
                            <label
                                class="flex h-[39px] items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-600">
                                <input type="checkbox"
                                    class="text-brand-600 focus:ring-brand-500 rounded border-gray-300"
                                    x-model="spaceForm.is_active" />
                                Active
                            </label>
                        </div>
                    </template>

                    <div class="flex gap-2 border-t border-gray-100 pt-4">
                        <button type="submit" class="layout-primary-btn flex-1" :disabled="saving">
                            <i class="fa-solid fa-circle-notch fa-spin text-xs" x-show="saving"></i>
                            <i class="fa-solid fa-floppy-disk text-xs" x-show="!saving"></i>
                            Save
                        </button>
                        <button type="button" class="layout-danger-btn" @click="deleteActiveNode()"
                            :disabled="saving">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </div>
                </form>

                <template x-if="activeNode.type !== 'growing_space'">
                    <div class="space-y-3 border-t border-gray-100 pt-5">
                        <p class="layout-panel-title">Add Under Selection</p>
                        <div class="grid grid-cols-1 gap-2">
                            <button type="button" class="layout-secondary-btn justify-start"
                                @click="prepareCreateZone()">
                                <i class="fa-solid fa-layer-group text-xs"></i>
                                Add Zone
                            </button>
                            <button type="button" class="layout-secondary-btn justify-start"
                                @click="prepareCreateSpace()">
                                <i class="fa-solid fa-seedling text-xs"></i>
                                Add Growing Space
                            </button>
                            <button type="button" class="layout-secondary-btn justify-start" @click="prepareBulk()">
                                <i class="fa-solid fa-table-cells text-xs"></i>
                                Bulk Generate Spaces
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="inspectorMode === 'create-site'">
            <form class="space-y-4" @submit.prevent="createSite()">
                <div class="flex items-center justify-between">
                    <p class="layout-panel-title">Create Site</p>
                    <button type="button" class="layout-icon-btn" @click="inspectorMode = 'details'"
                        title="Cancel">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <div>
                    <label class="layout-field-label">Site Name</label>
                    <input type="text" class="layout-input" x-model="siteForm.name" required />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="layout-field-label">Sort Order</label>
                        <input type="number" min="0" class="layout-input"
                            x-model.number="siteForm.sort_order" />
                    </div>
                    <label class="flex items-end">
                        <span
                            class="flex h-[39px] w-full items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-600">
                            <input type="checkbox" class="text-brand-600 focus:ring-brand-500 rounded border-gray-300"
                                x-model="siteForm.is_active" />
                            Active
                        </span>
                    </label>
                </div>
                <button type="submit" class="layout-primary-btn w-full" :disabled="saving">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Create Site
                </button>
            </form>
        </template>

        <template x-if="inspectorMode === 'create-zone'">
            <form class="space-y-4" @submit.prevent="createZone()">
                <div class="flex items-center justify-between">
                    <p class="layout-panel-title">Create Zone</p>
                    <button type="button" class="layout-icon-btn" @click="inspectorMode = 'details'"
                        title="Cancel">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <div>
                    <label class="layout-field-label">Zone Name</label>
                    <input type="text" class="layout-input" x-model="zoneForm.name" required />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="layout-field-label">Sort Order</label>
                        <input type="number" min="0" class="layout-input"
                            x-model.number="zoneForm.sort_order" />
                    </div>
                    <label class="flex items-end">
                        <span
                            class="flex h-[39px] w-full items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-600">
                            <input type="checkbox" class="text-brand-600 focus:ring-brand-500 rounded border-gray-300"
                                x-model="zoneForm.is_active" />
                            Active
                        </span>
                    </label>
                </div>
                <button type="submit" class="layout-primary-btn w-full" :disabled="saving">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Create Zone
                </button>
            </form>
        </template>

        <template x-if="inspectorMode === 'create-space'">
            <form class="space-y-4" @submit.prevent="createSpace()">
                <div class="flex items-center justify-between">
                    <p class="layout-panel-title">Create Growing Space</p>
                    <button type="button" class="layout-icon-btn" @click="inspectorMode = 'details'"
                        title="Cancel">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <div>
                    <label class="layout-field-label">Name</label>
                    <input type="text" class="layout-input" x-model="spaceForm.name" required />
                </div>
                <div>
                    <label class="layout-field-label">Growing Space Type</label>
                    <x-alpine-select name="growing_space_type_id" model="spaceForm.growing_space_type_id"
                        items="growingSpaceTypes" :required="true" :allow-empty="false" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="layout-field-label">Capacity</label>
                        <input type="number" min="0.01" step="0.01" class="layout-input"
                            x-model="spaceForm.capacity" required />
                    </div>
                    <div>
                        <label class="layout-field-label">Sort Order</label>
                        <input type="number" min="0" class="layout-input"
                            x-model.number="spaceForm.sort_order" />
                    </div>
                </div>
                <label
                    class="flex h-[39px] items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-600">
                    <input type="checkbox" class="text-brand-600 focus:ring-brand-500 rounded border-gray-300"
                        x-model="spaceForm.is_active" />
                    Active
                </label>
                <button type="submit" class="layout-primary-btn w-full"
                    :disabled="saving || !growingSpaceTypes.length">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Create Growing Space
                </button>
            </form>
        </template>

        <template x-if="inspectorMode === 'bulk'">
            <form class="space-y-4" @submit.prevent="previewBulk()">
                <div class="flex items-center justify-between">
                    <p class="layout-panel-title">Bulk Generate</p>
                    <button type="button" class="layout-icon-btn" @click="inspectorMode = 'details'"
                        title="Cancel">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <div>
                    <label class="layout-field-label">Growing Space Type</label>
                    <x-alpine-select name="bulk_growing_space_type_id" model="bulkForm.growing_space_type_id"
                        items="growingSpaceTypes" :required="true" :allow-empty="false" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="layout-field-label">Capacity</label>
                        <input type="number" min="0.01" step="0.01" class="layout-input"
                            x-model="bulkForm.capacity" required />
                    </div>
                    <div>
                        <label class="layout-field-label">Prefix</label>
                        <input type="text" class="layout-input" x-model="bulkForm.prefix" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="layout-field-label">Row Start</label>
                        <input type="number" min="1" class="layout-input"
                            x-model.number="bulkForm.row_start" required />
                    </div>
                    <div>
                        <label class="layout-field-label">Row End</label>
                        <input type="number" min="1" class="layout-input" x-model.number="bulkForm.row_end"
                            required />
                    </div>
                    <div>
                        <label class="layout-field-label">Column Start</label>
                        <input type="number" min="1" class="layout-input"
                            x-model.number="bulkForm.col_start" required />
                    </div>
                    <div>
                        <label class="layout-field-label">Column End</label>
                        <input type="number" min="1" class="layout-input" x-model.number="bulkForm.col_end"
                            required />
                    </div>
                </div>
                <div>
                    <label class="layout-field-label">Name Template</label>
                    <input type="text" class="layout-input" x-model="bulkForm.name_template" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="layout-field-label">Sort Starts At</label>
                        <input type="number" min="0" class="layout-input"
                            x-model.number="bulkForm.sort_order_start" />
                    </div>
                    <label class="flex items-end">
                        <span
                            class="flex h-[39px] w-full items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-600">
                            <input type="checkbox" class="text-brand-600 focus:ring-brand-500 rounded border-gray-300"
                                x-model="bulkForm.skip_conflicts" />
                            Skip duplicates
                        </span>
                    </label>
                </div>
                <button type="submit" class="layout-secondary-btn w-full"
                    :disabled="saving || !growingSpaceTypes.length">
                    <i class="fa-solid fa-eye text-xs"></i>
                    Preview Grid
                </button>

                <template x-if="bulkPreviewReady">
                    <div class="space-y-3 border-t border-gray-100 pt-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-black text-gray-700">
                                <span x-text="bulkPreview.length"></span> spaces
                            </p>
                            <p class="text-xs font-black text-red-600" x-show="bulkConflictCount">
                                <span x-text="bulkConflictCount"></span> conflicts
                            </p>
                        </div>
                        <div class="max-h-44 overflow-y-auto rounded-lg border border-gray-200">
                            <template x-for="row in bulkPreview" :key="`${row.row}-${row.col}-${row.name}`">
                                <div
                                    class="flex items-center justify-between border-b border-gray-100 px-3 py-2 last:border-0">
                                    <span class="truncate text-xs font-bold text-gray-700" x-text="row.name"></span>
                                    <span class="ml-2 rounded px-1.5 py-0.5 text-[10px] font-black"
                                        :class="row.conflict ?
                                            'bg-red-50 text-red-600' :
                                            'bg-emerald-50 text-emerald-700'"
                                        x-text="row.conflict ? 'Exists' : 'New'"></span>
                                </div>
                            </template>
                        </div>
                        <button type="button" class="layout-primary-btn w-full" @click="generateBulk()"
                            :disabled="saving || (bulkConflictCount && !bulkForm.skip_conflicts)">
                            <i class="fa-solid fa-table-cells text-xs"></i>
                            Generate Spaces
                        </button>
                    </div>
                </template>
            </form>
        </template>

        <template x-if="inspectorMode === 'types'">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <p class="layout-panel-title">Space Types</p>
                    <button type="button" class="layout-icon-btn" @click="inspectorMode = 'details'"
                        title="Close">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Inline create form --}}
                <form class="space-y-3 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-3"
                    @submit.prevent="saveNewType()">
                    <p class="text-[10px] font-black tracking-widest text-gray-400 uppercase">New Type</p>
                    <div>
                        <label class="layout-field-label">Name</label>
                        <input type="text" class="layout-input" x-model="typeForm.name"
                            placeholder="e.g. Bench, Tray" required />
                    </div>
                    <div>
                        <label class="layout-field-label">Capacity Unit</label>
                        <x-alpine-select name="capacity_unit" model="typeForm.capacity_unit"
                            items="capacityUnitOptions" :required="true" :allow-empty="false" />
                    </div>
                    <div x-show="typeForm.capacity_unit === 'custom'">
                        <label class="layout-field-label">Custom Unit Label</label>
                        <input type="text" class="layout-input" x-model="typeForm.custom_unit_label"
                            placeholder="e.g. Trays, Packs" />
                    </div>
                    <button type="submit" class="layout-primary-btn w-full" :disabled="typeSaving">
                        <i class="fa-solid fa-circle-notch fa-spin text-xs" x-show="typeSaving"></i>
                        <i class="fa-solid fa-plus text-xs" x-show="!typeSaving"></i>
                        Add Type
                    </button>
                </form>

                {{-- Existing types list --}}
                <div class="space-y-1">
                    <template x-if="growingSpaceTypes.length === 0">
                        <p class="py-6 text-center text-xs text-gray-400">No types yet. Add one above.</p>
                    </template>
                    <template x-for="type in growingSpaceTypes" :key="type.id">
                        <div
                            class="group flex items-center gap-2 rounded-lg border border-gray-100 bg-white px-3 py-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-black text-gray-800" x-text="type.name"></p>
                                <p class="text-[10px] text-gray-400" x-text="capacityUnit(type.id)"></p>
                            </div>
                            <button type="button"
                                class="invisible shrink-0 text-red-400 group-hover:visible hover:text-red-600"
                                title="Delete" @click="deleteType(type)">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</aside>
