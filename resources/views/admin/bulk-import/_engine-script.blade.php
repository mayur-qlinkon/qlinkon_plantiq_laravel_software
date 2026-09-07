{{--
    Import engine — upload + chunked processing for one import type.

    @once so a page holding several panels (or a modal alongside a table)
    registers the factory a single time.

    Config:
      type          — URL segment, e.g. 'categories' or 'product-images'
      isZip         — bool, switches file validation to ZIP
      productLimit  — int|null   plan cap, for the limit banner
      productCount  — int        current usage
      limitExceeded — bool       already at the cap on page load
      onComplete    — function|null, called once the import finishes
--}}
@once
    @push('scripts')
        <script>
            window.importEngine = function(config = {}) {
                return {
                    type: config.type || '',
                    isZip: config.isZip || false,
                    maxBytes: config.maxBytes || 0,                

                    // ── Upload state ──
                    selectedFile: null,
                    uploading: false,
                    uploadError: '',
                    dragOver: false,


                    // ── Plan limit ──
                    productLimit: config.productLimit ?? null,
                    productCount: config.productCount ?? 0,
                    limitExceeded: config.limitExceeded ?? false,
                    limitExceededDetails: null,

                    // ── Processing state ──
                    importId: null,
                    currentOffset: 0,
                    totalRows: 0,
                    processedRows: 0,
                    successRows: 0,
                    failedRows: 0,
                    skippedRows: 0,
                    createdRows: 0,
                    updatedRows: 0,
                    limitSkippedRows: 0,
                    duplicateCount: 0,

                    // Categories and units the importer created on the fly.
                    // Accumulated across chunks because each response carries
                    // only what that chunk made.
                    createdRefs: {
                        categories: [],
                        units: []
                    },
                    importRunIsDryRun: false,
                    chunkError: false,
                    done: false,
                    processing: false,

                    get progressPercent() {
                        if (this.totalRows === 0) return 0;
                        return Math.round((this.processedRows / this.totalRows) * 100);
                    },
                    get progressText() {
                        return `${this.processedRows} / ${this.totalRows} (${this.progressPercent}%)`;
                    },

                    /** True while a run is in flight — used to guard closing the modal. */
                    get isBusy() {
                        return this.uploading || this.processing;
                    },

                    // ── File handlers ──
                    handleFileSelect(event) {
                        const file = event.target.files[0];
                        if (file) this.acceptFile(file);
                    },
                    handleFileDrop(event) {
                        const file = event.dataTransfer.files[0];
                        if (file) this.acceptFile(file);
                    },

                    /** One validation path for both CSV and ZIP, chosen by config.isZip. */
                    acceptFile(file) {
                        const name = file.name.toLowerCase();

                        if (this.isZip) {
                            if (!name.endsWith('.zip')) {
                                this.uploadError = 'Please select a valid ZIP file.';
                                return;
                            }
                        } else if (!name.endsWith('.csv') && file.type !== 'text/csv') {
                            this.uploadError = 'Please select a valid CSV file.';
                            return;
                        }

                        // Checked here, not on the server: a file past
                        // post_max_size makes PHP drop the whole request body,
                        // CSRF token included, so the user would get a bare
                        // "419 Page Expired" with no clue what went wrong.
                        if (this.maxBytes > 0 && file.size > this.maxBytes) {
                            this.uploadError = `File is ${this.formatFileSize(file.size)} — the limit is ${this.formatFileSize(this.maxBytes)}. Split it into smaller files and upload them one after another.`;
                            return;
                        }

                        this.selectedFile = file;
                        this.uploadError = '';
                    },

                    formatFileSize(bytes) {
                        if (!bytes) return '';
                        if (bytes < 1024) return bytes + ' B';
                        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                        return (bytes / 1048576).toFixed(1) + ' MB';
                    },

                    csrf() {
                        return document.querySelector('meta[name="csrf-token"]').content;
                    },

                    // ── Upload ──
                    async startUpload() {
                        if (!this.selectedFile) return;

                        this.uploading = true;
                        this.uploadError = '';
                        this.limitExceeded = false;
                        this.limitExceededDetails = null;

                        const formData = new FormData();
                        formData.append('file', this.selectedFile);

                        // Fixed, not user-selectable. This flow targets people
                        // doing a one-shot data load who cannot confidently
                        // judge "create or update" against "update only";
                        // create_only leaves existing rows untouched instead of
                        // silently overwriting them, so a re-upload is safe.
                        formData.append('import_mode', 'create_only');

                        // Duplicate detection and dry runs are row concepts — a ZIP of
                        // images has neither.
                        if (!this.isZip) {
                            formData.append('duplicate_mode', 'skip');
                        }

                        try {
                            const res = await fetch(`/admin/bulk-import/${this.type}/upload`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf()
                                },
                                body: formData,
                            });
                            const data = await res.json();

                            if (!res.ok) {
                                if (data.limit_exceeded) {
                                    this.limitExceeded = true;
                                    this.limitExceededDetails = data;
                                } else {
                                    this.uploadError = data.error || data.message || 'Upload failed.';
                                }
                                this.uploading = false;
                                return;
                            }

                            this.importId = data.import_id;
                            this.currentOffset = 0;
                            this.totalRows = data.total_rows;
                            this.duplicateCount = data.duplicate_count || 0;
                            this.importRunIsDryRun = !!data.is_dry_run;
                            this.processedRows = 0;
                            this.successRows = 0;
                            this.failedRows = 0;
                            this.skippedRows = 0;
                            this.createdRows = 0;
                            this.updatedRows = 0;
                            this.limitSkippedRows = 0;
                            this.createdRefs = {
                                categories: [],
                                units: []
                            };
                            this.chunkError = false;
                            this.done = false;
                            this.uploading = false;

                            this.processNextChunk(0);

                        } catch (e) {
                            this.uploadError = 'Network error. Please try again.';
                            this.uploading = false;
                        }
                    },

                    // ── Chunked processing ──
                    async processNextChunk(offset) {
                        if (this.done) return;

                        this.processing = true;
                        this.chunkError = false;
                        this.currentOffset = offset;

                        try {
                            const res = await fetch(`/admin/bulk-import/${this.type}/process`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                                body: JSON.stringify({
                                    import_id: this.importId,
                                    offset
                                }),
                            });
                            const data = await res.json();

                            if (!res.ok) {
                                this.chunkError = true;
                                this.processing = false;
                                return;
                            }

                            this.processedRows = data.processed;
                            this.successRows = data.success;
                            this.failedRows = data.failed;
                            this.skippedRows = data.skipped || 0;
                            this.createdRows = data.created || 0;
                            this.updatedRows = data.updated || 0;
                            this.limitSkippedRows = data.limit_skipped || 0;
                            this.collectCreatedRefs(data.created_refs);
                            if (typeof data.is_dry_run !== 'undefined') this.importRunIsDryRun = !!data.is_dry_run;

                            if (data.done) {
                                this.done = true;
                                this.processing = false;
                                this.$nextTick(() => {
                                    if (window.lucide) lucide.createIcons();
                                });

                                // Lets a host (the modal) refresh its table once real
                                // records were written. Dry runs changed nothing.
                                if (typeof config.onComplete === 'function' && !this.importRunIsDryRun) {
                                    config.onComplete(this);
                                }
                            } else {
                                this.processNextChunk(data.next_offset);
                            }
                        } catch (e) {
                            this.chunkError = true;
                            this.processing = false;
                        }
                    },

                    /**
                     * Merge one chunk's auto-created names into the running set.
                     * Deduplicated: the same category can legitimately be
                     * created once and then reused by later rows in the file.
                     */
                    collectCreatedRefs(refs) {
                        if (!refs) return;

                        for (const key of ['categories', 'units']) {
                            const incoming = refs[key] || [];
                            this.createdRefs[key] = [...new Set([...this.createdRefs[key], ...incoming])];
                        }
                    },

                    get hasCreatedRefs() {
                        return this.createdRefs.categories.length > 0 || this.createdRefs.units.length > 0;
                    },

                    // ── Reset ──
                    resetState() {
                        this.selectedFile = null;
                        this.uploading = false;
                        this.uploadError = '';
                        this.importId = null;
                        this.currentOffset = 0;
                        this.totalRows = 0;
                        this.processedRows = 0;
                        this.successRows = 0;
                        this.failedRows = 0;
                        this.skippedRows = 0;
                        this.createdRows = 0;
                        this.updatedRows = 0;
                        this.limitSkippedRows = 0;
                        this.duplicateCount = 0;
                        this.createdRefs = {
                            categories: [],
                            units: []
                        };
                        this.importRunIsDryRun = false;
                        this.chunkError = false;
                        this.done = false;
                        this.processing = false;
                        this.limitExceeded = false;
                        this.limitExceededDetails = null;
                        this.$nextTick(() => {
                            if (window.lucide) lucide.createIcons();
                        });
                    },
                };
            };



            /**
             * Modal host around one import engine.
             *
             * Object.defineProperties rather than spread: spreading would evaluate the
             * engine's getters (progressPercent, isBusy) once and freeze them as static
             * values, so the progress bar would never move.
             */
            window.importModal = function(config = {}) {
                const engine = window.importEngine(config);

                return Object.defineProperties({
                    open: false,

                    openModal() {
                        this.open = true;
                        document.body.style.overflow = 'hidden';
                        this.$nextTick(() => {
                            if (window.lucide) lucide.createIcons();
                        });
                    },

                    /**
                     * A chunked import that is abandoned half-way leaves partial data
                     * behind, so an in-flight run must be confirmed before closing.
                     */
                    requestClose() {
                        if (this.isBusy && !confirm(
                                'An import is still running. Closing now will leave it half-finished. Close anyway?'
                            )) {
                            return;
                        }
                        this.closeModal();
                    },

                    closeModal() {
                        const shouldReload = this.done && !this.importRunIsDryRun;

                        this.open = false;
                        document.body.style.overflow = '';
                        this.resetState();

                        // The table behind the modal is now stale — only reload when real
                        // records were written.
                        if (shouldReload) window.location.reload();
                    },
                }, Object.getOwnPropertyDescriptors(engine));
            };
        </script>
    @endpush
@endonce
