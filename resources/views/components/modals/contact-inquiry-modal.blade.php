{{--
    REUSABLE CONTACT INQUIRY MODAL
    Usage: <x-modals.contact-inquiry-modal />
    Trigger from anywhere: window.openContactModal()
--}}

<div
    x-data="contactInquiryModal('{{ route('admin.contact.store') }}')"
    x-show="open"
    x-cloak
    @keydown.escape.window="close()"
    class="fixed inset-0 z-[9990] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true">

    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
        @click="close()">
    </div>

    {{-- Modal Card --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col"
        style="max-height: 90vh;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-brand-50 flex items-center justify-center shrink-0">
                    <i data-lucide="headphones" class="w-5 h-5 text-brand-600"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Contact Support</h3>
                    <p class="text-xs text-gray-400 font-medium mt-0.5">We typically respond within 24 hours</p>
                </div>
            </div>
            <button
                @click="close()"
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Success State --}}
        <div x-show="submitted" class="flex flex-col items-center justify-center text-center px-8 py-14 gap-4">
            <div class="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center mb-2">
                <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-500"></i>
            </div>
            <h4 class="text-lg font-bold text-gray-900">Message Sent!</h4>
            <p class="text-sm text-gray-500 max-w-xs" x-text="successMessage"></p>
            <button
                @click="close()"
                type="button"
                class="mt-2 px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors">
                Done
            </button>
        </div>

        {{-- Form --}}
        <div x-show="!submitted" class="overflow-y-auto flex-1">
            <form @submit.prevent="submit()" class="px-6 py-6 space-y-4">

                {{-- Message --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        x-model="form.message"
                        rows="4"
                        placeholder="Describe your issue in detail..."
                        maxlength="2000"
                        class="w-full px-4 py-2.5 text-sm bg-gray-50 border rounded-xl outline-none transition-all resize-none focus:bg-white focus:ring-2 focus:ring-brand-500/20"
                        :class="errors.message ? 'border-red-400 focus:border-red-400' : 'border-gray-200 focus:border-brand-400'"
                    ></textarea>
                    <div class="flex items-center justify-between mt-1">
                        <p x-show="errors.message" x-text="errors.message" class="text-xs text-red-500 font-medium"></p>
                        <p class="text-xs text-gray-400 ml-auto" x-text="form.message.length + '/2000'"></p>
                    </div>
                </div>

                {{-- General Error --}}
                <div x-show="generalError" class="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-100 rounded-xl">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0"></i>
                    <p x-text="generalError" class="text-xs text-red-600 font-medium"></p>
                </div>

            </form>
        </div>

        {{-- Footer / Submit --}}
        <div x-show="!submitted" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-3 shrink-0">
            <button
                @click="close()"
                type="button"
                class="px-5 py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors">
                Cancel
            </button>
            <button
                @click="submit()"
                type="button"
                :disabled="loading"
                class="flex items-center gap-2 px-6 py-2.5 bg-brand-600 hover:bg-brand-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                <svg x-show="loading" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"/>
                    <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <i x-show="!loading" data-lucide="send" class="w-4 h-4"></i>
                <span x-text="loading ? 'Sending...' : 'Send Message'"></span>
            </button>
        </div>

    </div>
</div>

@push('scripts')
<script>
    (function registerContactModal() {
        function register() {
            Alpine.data('contactInquiryModal', (endpointUrl) => ({
                open: false,
                loading: false,
                submitted: false,
                successMessage: '',
                generalError: '',
                form: { message: '' },
                errors: { message: '' },

                init() {
                    // Expose globally so any button can trigger it
                    window.openContactModal = () => {
                        this.resetForm();
                        this.open = true;
                        this.$nextTick(() => window.initIcons(this.$el));
                    };
                },

                close() {
                    this.open = false;
                },

                resetForm() {
                    this.form        = { message: '' };
                    this.errors      = { message: '' };
                    this.generalError = '';
                    this.submitted   = false;
                    this.loading     = false;
                },

                validate() {
                    this.errors = { message: '' };
                    let valid = true;

                    if (!this.form.message.trim()) {
                        this.errors.message = 'Message is required.';
                        valid = false;
                    }
                    return valid;
                },

                async submit() {
                    if (!this.validate()) return;

                    this.loading = true;
                    this.generalError = '';

                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const res = await fetch(endpointUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.form),
                        });

                        const data = await res.json();

                        if (res.ok && data.success) {
                            this.successMessage = data.message || 'Your message has been sent!';
                            this.submitted = true;
                            this.$nextTick(() => window.initIcons(this.$el));
                        } else if (res.status === 422 && data.errors) {
                            // Laravel validation errors
                            Object.keys(data.errors).forEach(field => {
                                if (this.errors.hasOwnProperty(field)) {
                                    this.errors[field] = data.errors[field][0];
                                }
                            });
                        } else {
                            this.generalError = data.message || 'Something went wrong. Please try again.';
                        }
                    } catch (e) {
                        this.generalError = 'Network error. Please check your connection.';
                        console.error('Contact inquiry error:', e);
                    } finally {
                        this.loading = false;
                    }
                },
            }));
        }

        if (window.Alpine) {
            register();
        } else {
            document.addEventListener('alpine:init', register, { once: true });
        }
    })();
</script>
@endpush