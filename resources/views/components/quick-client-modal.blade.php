{{-- Closes on the X button and Escape only. A half-filled client form is
     easy to lose to a stray click on the backdrop, so click-away is
     deliberately not wired up here. --}}
<div x-show="isClientModalOpen" x-cloak @keydown.escape.window="isClientModalOpen = false"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl flex flex-col overflow-hidden" x-show="isClientModalOpen"
        x-transition>

        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="text-[15px] font-bold text-gray-800">Quick Add Client</h3>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-semibold">New Customer Entry</p>
            </div>
            <button type="button" @click="isClientModalOpen = false"
                class="text-gray-400 hover:text-red-500 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-6 grid grid-cols-2 gap-5">
            <div class="col-span-2 sm:col-span-1">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="newClient.name" placeholder="John Doe"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-4 focus:ring-[#108c2a]/5 transition-all">
            </div>

            <div class="col-span-2 sm:col-span-1">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    Phone
                </label>
                <input type="text" x-model="newClient.phone"
                    @input="newClient.phone = newClient.phone.replace(/[^0-9]/g, '').slice(0, 10)"
                    placeholder="10-digit number"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-4 focus:ring-[#108c2a]/5 transition-all">
            </div>

            <div class="col-span-2 sm:col-span-1">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    City
                </label>
                <input type="text" x-model="newClient.city"
                    @input="newClient.city = newClient.city.replace(/[^a-zA-Z\s]/g, '')" placeholder="e.g. Mumbai"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#108c2a] bg-gray-50/30">
            </div>

            <div class="col-span-2 sm:col-span-1">
                {{-- Bound to state_id, the key the payload actually sends. It
                     was bound to newClient.state, which Alpine happily created
                     as a new property while state_id stayed empty — so every
                     state picked here was silently discarded. --}}
                <x-state-select x-model="newClient.state_id" label="State" />
            </div>

            <div class="col-span-2">
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    GST Treatment
                </label>
                <select x-model="newClient.registration_type"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#108c2a] bg-gray-50/30 appearance-none cursor-pointer">
                    <option value="unregistered">Unregistered (B2C)</option>
                    <option value="registered">Regular (B2B)</option>
                    <option value="composition">Composition</option>
                </select>
            </div>
        </div>

        <div class="p-5 border-t border-gray-100 bg-gray-50/30 grid grid-cols-2 gap-3">
            <button type="button" @click="isClientModalOpen = false"
                class="bg-white border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest py-3 rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button type="button" @click="saveQuickClient()" :disabled="!newClient.name.trim()"
                :class="!newClient.name.trim() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[#0d7623]'"
                class="bg-[#108c2a] text-white font-bold text-xs uppercase tracking-widest py-3 rounded-lg shadow-lg shadow-green-900/10 transition-all">
                Save Client
            </button>
        </div>
    </div>
</div>
