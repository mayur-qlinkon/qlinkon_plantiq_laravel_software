@extends('layouts.platform')
@section('title', 'Plan Calculator')
@section('header', 'Plan Calculator')

@section('content')
    <div class="mb-8 border-b border-gray-200 pb-5">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Plan Calculator</h2>
        <p class="mt-2 text-sm text-gray-500">
            Draft a quote for prospective clients. This is a sandbox environment; no data is persisted or saved.
        </p>
    </div>

    <div x-data="planCalculator(@js($addons), @js($plantProfiles), @js($profileKits), @js($plantSlug))" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- ── LEFT: Selection ── --}}
        <div class="lg:col-span-7 xl:col-span-8 space-y-8">

            {{-- Add-ons Section --}}
            <section>
                <div class="mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">1. Select Add-ons</h3>
                    <p class="mt-1 text-sm text-gray-500">Choose at least one core feature for the plan.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <template x-for="addon in addons" :key="addon.id">
                        <label
                            class="relative flex cursor-pointer rounded-xl border bg-white p-4 shadow-sm transition-all duration-200 focus:outline-none"
                            :class="selectedAddons.includes(addon.id) ?
                                'border-brand-600 ring-1 ring-brand-600 bg-brand-50/10' :
                                'border-gray-200 hover:border-brand-300 hover:bg-gray-50'">
                            <input type="checkbox" :value="addon.id" x-model.number="selectedAddons" class="sr-only" />
                            <div class="flex w-full items-center justify-between">
                                <div class="flex items-center gap-3">
                                    {{-- Custom Checkbox UI --}}
                                    <div class="flex h-5 w-5 items-center justify-center rounded border transition-colors"
                                        :class="selectedAddons.includes(addon.id) ? 'bg-brand-600 border-brand-600' :
                                            'border-gray-300 bg-white'">
                                        <svg x-show="selectedAddons.includes(addon.id)" class="h-3.5 w-3.5 text-white"
                                            viewBox="0 0 14 14" fill="none">
                                            <path d="M3 8L6 11L11 3.5" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900" x-text="addon.name"></p>
                                        <p class="text-xs text-gray-500 mt-0.5"
                                            x-text="addon.slug === plantSlug ? 'Dynamic pricing applied' : money(addon.price)">
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </template>
                </div>
            </section>

            {{-- Plant Education Section --}}
            <section x-show="plantEduSelected" x-collapse x-cloak>
                <div class="rounded-xl border border-brand-100 bg-brand-50/30 p-6">
                    <div class="mb-5">
                        <h3 class="text-base font-medium leading-6 text-gray-900">Plant Education Requirements</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Because you selected Plant Education, please define the profile and kit size.
                        </p>
                        <p x-show="!plantProfileId || !profileKitId" class="mt-2 text-sm font-medium text-amber-700">
                            Pricing will appear once both are chosen.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Plant Profile</label>
                            <select x-model.number="plantProfileId"
                                class="mt-1 block w-full rounded-lg border-gray-300 py-2.5 pl-3 pr-10 text-base focus:border-brand-500 focus:outline-none focus:ring-brand-500 sm:text-sm shadow-sm transition-colors">
                                <option :value="null">Select a profile...</option>
                                <template x-for="p in plantProfiles" :key="p.id">
                                    <option :value="p.id" x-text="`${p.name} (${p.plant_limit} plants)`"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Profile Kit</label>
                            <select x-model.number="profileKitId"
                                class="mt-1 block w-full rounded-lg border-gray-300 py-2.5 pl-3 pr-10 text-base focus:border-brand-500 focus:outline-none focus:ring-brand-500 sm:text-sm shadow-sm transition-colors">
                                <option :value="null">Select a kit...</option>
                                <template x-for="k in profileKits" :key="k.id">
                                    <option :value="k.id" x-text="`${k.name} — ${money(k.price)} / plant`">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Coupon Section --}}
            <section>
                <div class="mb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">2. Apply Promotions</h3>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative w-full max-w-sm rounded-lg shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm2.25 8.5a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" x-model="coupon" placeholder="Discount code (Optional)"
                            class="block w-full rounded-lg border-gray-300 pl-10 py-2.5 uppercase text-sm focus:border-brand-500 focus:ring-brand-500 transition-colors" />
                    </div>
                </div>
            </section>
        </div>

        {{-- ── RIGHT: Quote Breakdown ── --}}
        <div class="lg:col-span-5 xl:col-span-4">
            <div class="sticky top-6 overflow-hidden rounded-2xl bg-gray-50 border border-gray-200 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 bg-gray-100/50">
                    <h3 class="text-base font-semibold text-gray-900">Estimated Quote</h3>
                </div>

                <div class="p-6">
                    {{-- Empty State --}}
                    <template x-if="!loading && !error && !quote">
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                            </svg>
                            <p class="mt-3 text-sm text-gray-500">Select options on the left to generate a calculation.</p>
                        </div>
                    </template>

                    {{-- Loading Skeleton --}}
                    <template x-if="loading">
                        <div class="animate-pulse space-y-4">
                            <div class="flex justify-between">
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                            </div>
                            <div class="flex justify-between">
                                <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/5"></div>
                            </div>
                            <div class="h-px bg-gray-200 w-full my-4"></div>
                            <div class="flex justify-between">
                                <div class="h-5 bg-gray-200 rounded w-1/3"></div>
                                <div class="h-6 bg-gray-300 rounded w-1/3"></div>
                            </div>
                        </div>
                    </template>

                    {{-- Error State --}}
                    <template x-if="!loading && error">
                        <div class="rounded-lg bg-red-50 p-4 border border-red-100">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Calculation Error</h3>
                                    <p class="mt-1 text-sm text-red-700" x-text="error"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Populated Quote --}}
                    <template x-if="!loading && !error && quote">
                        <div class="space-y-4">

                            {{-- Line Items --}}
                            <div class="space-y-3">
                                {{-- Plant Profile Breakdown --}}
                                <template x-if="quote.lines.plant_profile">
                                    <div class="rounded-lg bg-white p-3 border border-gray-100 shadow-sm space-y-2 mb-4">
                                        <div class="flex justify-between items-start text-sm">
                                            <div>
                                                <span class="font-medium text-gray-700 block"
                                                    x-text="quote.lines.plant_profile.name"></span>
                                                <span class="text-xs text-gray-500">Base profile fee</span>
                                            </div>
                                            <span class="font-medium text-gray-900"
                                                x-text="money(quote.lines.plant_profile.price)"></span>
                                        </div>
                                        <template x-if="quote.lines.profile_kit">
                                            <div
                                                class="flex justify-between items-start text-sm pt-2 border-t border-gray-50">
                                                <div>
                                                    <span class="text-gray-600 block"
                                                        x-text="quote.lines.profile_kit.name"></span>
                                                    <span class="text-xs text-gray-500"
                                                        x-text="`${quote.lines.profile_kit.multiplier} plants @ ${money(quote.lines.profile_kit.kit_price)}`"></span>
                                                </div>
                                                <span class="font-medium text-gray-900"
                                                    x-text="money(quote.lines.profile_kit.price)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Standard Add-on Lines --}}
                                <template x-for="line in quote.lines.addons" :key="line.id">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600" x-text="line.name"></span>
                                        <span class="font-medium text-gray-900" x-text="money(line.price)"></span>
                                    </div>
                                </template>
                            </div>

                            <hr class="border-gray-200 border-dashed" />

                            {{-- Totals --}}
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Subtotal</span>
                                    <span class="font-medium text-gray-900" x-text="money(quote.subtotal)"></span>
                                </div>

                                <template x-if="quote.coupon">
                                    <div>
                                        <template x-if="quote.coupon.applied">
                                            <div
                                                class="flex justify-between text-sm text-emerald-600 bg-emerald-50 rounded px-2 py-1 -mx-2">
                                                <span class="font-medium"
                                                    x-text="`Discount (${quote.coupon.code})`"></span>
                                                <span class="font-medium" x-text="`− ${money(quote.discount)}`"></span>
                                            </div>
                                        </template>
                                        <template x-if="!quote.coupon.applied">
                                            <div class="flex items-center gap-1.5 text-sm text-red-500 mt-1">
                                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                <span x-text="quote.coupon.error"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <div class="pt-4 border-t border-gray-200 flex justify-between items-end">
                                <div>
                                    <span class="block text-base font-bold text-gray-900">Total</span>
                                    <span class="block text-xs text-gray-400 font-normal">Excluding applicable taxes</span>
                                </div>
                                <span class="text-3xl font-extrabold text-brand-600 tracking-tight"
                                    x-text="money(quote.total)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.planCalculator = function(addons, plantProfiles, profileKits, plantSlug) {
            return {
                addons: addons,
                plantProfiles: plantProfiles,
                profileKits: profileKits,
                plantSlug: plantSlug,

                selectedAddons: [],
                plantProfileId: null,
                profileKitId: null,
                coupon: '',

                quote: null,
                error: null,
                loading: false,

                _timer: null,
                _seq: 0,

                init() {
                    this.$watch('selectedAddons', () => this.schedule());
                    this.$watch('plantProfileId', () => this.schedule());
                    this.$watch('profileKitId', () => this.schedule());
                    this.$watch('coupon', () => this.schedule());
                },

                get plantEduSelected() {
                    return this.addons
                        .filter(a => this.selectedAddons.includes(a.id))
                        .some(a => a.slug === this.plantSlug);
                },

                schedule() {
                    clearTimeout(this._timer);
                    this._timer = setTimeout(() => this.fetchQuote(), 350);
                },

                async fetchQuote() {
                    if (this.selectedAddons.length === 0) {
                        this.quote = null;
                        this.error = null;
                        return;
                    }

                    // Plant Education needs a profile and a kit before a price
                    // exists. The addon watcher fires the moment it is ticked,
                    // so without this the server was asked to price an
                    // incomplete selection and its "select a valid plant
                    // profile" reply surfaced as a calculation error.
                    // The placeholder option yields 0 under x-model.number,
                    // so a falsy check covers both null and 0.
                    if (this.plantEduSelected && (!this.plantProfileId || !this.profileKitId)) {
                        this.quote = null;
                        this.error = null;
                        this.loading = false;
                        return;
                    }

                    const seq = ++this._seq;

                    this.loading = true;
                    this.error = null;

                    try {
                        const response = await fetch("{{ route('platform.plan-calculator.quote') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                addon_ids: this.selectedAddons,
                                plant_profile_id: this.plantProfileId,
                                profile_kit_id: this.profileKitId,
                                coupon: this.coupon,
                            }),
                        });

                        const data = await response.json();

                        if (seq !== this._seq) {
                            return;
                        }

                        if (data.valid) {
                            this.quote = data;
                        } else {
                            this.quote = null;
                            this.error = data.message || 'Could not calculate a price.';
                        }
                    } catch (e) {
                        if (seq === this._seq) {
                            this.quote = null;
                            this.error = 'Could not reach the server. Please try again.';
                        }
                    } finally {
                        if (seq === this._seq) {
                            this.loading = false;
                        }
                    }
                },

                money(value) {
                    return '₹' + Number(value || 0).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
            };
        };
    </script>
@endpush
