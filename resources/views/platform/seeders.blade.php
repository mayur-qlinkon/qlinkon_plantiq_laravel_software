@extends('layouts.platform')

@section('title', 'Database Seeders - Platform Admin')
@section('header', 'Visual Seeder Platform')

@section('content')
<div class="w-full mx-auto" x-data="seederManager()">
    
    {{-- Header & Context Selector --}}
    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Deployment Context</h2>
            <p class="text-sm text-gray-500 mt-1">Select a target company before running tenant-specific seeders.</p>
        </div>

        <div class="w-full md:w-80">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Target Company</label>
            <div class="relative">
                <select x-model="selectedCompany" class="w-full pl-4 pr-10 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-800 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 appearance-none cursor-pointer">
                    <option value="">-- Select a Company --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }} ({{ $company->slug }})</option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
            </div>
        </div>
    </div>

    {{-- Seeder Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($seeders as $key => $seeder)
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
                
                <div class="p-6 flex-1">
                    <div class="w-12 h-12 rounded-xl bg-{{ $seeder['color'] }}-50 flex items-center justify-center mb-4 border border-{{ $seeder['color'] }}-100">
                        <i class="fas fa-{{ $seeder['icon'] }} w-6 h-6 text-{{ $seeder['color'] }}-600"></i>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $seeder['name'] }}</h3>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4">{{ $seeder['description'] }}</p>
                    
                    @if($seeder['requires_company'])
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider bg-orange-50 text-orange-600 border border-orange-100">
                            <i class="fas fa-building w-3 h-3"></i> Requires Tenant
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-50 text-slate-600 border border-slate-200">
                            <i class="fas fa-globe w-3 h-3"></i> Global Scope
                        </span>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <button 
                        @click="executeSeeder('{{ $key }}', '{{ $seeder['name'] }}', {{ $seeder['requires_company'] ? 'true' : 'false' }})"
                        :disabled="loading === '{{ $key }}'"
                        class="w-full py-2.5 px-4 bg-white border border-gray-200 hover:bg-gray-100 hover:text-brand-600 text-gray-700 rounded-xl text-sm font-bold shadow-sm transition-colors flex items-center justify-center gap-2"
                        :class="loading === '{{ $key }}' ? 'opacity-50 cursor-not-allowed' : ''">
                        
                        <span x-show="loading !== '{{ $key }}'">Run Seeder</span>
                        <span x-show="loading === '{{ $key }}'" class="flex items-center gap-2 text-brand-600">
                            <i class="fas fa-spinner fa-spin w-4 h-4"></i> Deploying...
                        </span>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

@section('scripts')
<script>
    function seederManager() {
        return {
            selectedCompany: '',
            loading: null,

            executeSeeder(seederKey, seederName, requiresCompany) {
                // 1. Guardrail Check
                if (requiresCompany && !this.selectedCompany) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Select a Company',
                        text: 'This seeder requires a target company. Please select one from the dropdown above.',
                        confirmButtonColor: '#0f766e',
                        customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl' }
                    });
                    return;
                }

                // 2. Confirmation Check
                let confirmText = requiresCompany 
                    ? `Are you sure you want to deploy ${seederName} to the selected company?`
                    : `Are you sure you want to run the global ${seederName} seeder?`;

                Swal.fire({
                    title: 'Confirm Deployment',
                    text: confirmText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0f766e',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, Deploy Now',
                    customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl', cancelButton: 'rounded-xl' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.processExecution(seederKey);
                    }
                });
            },

            processExecution(seederKey) {
                this.loading = seederKey;

                fetch("{{ route('platform.seeders.execute') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        seeder_key: seederKey,
                        company_id: this.selectedCompany
                    })
                })
                .then(async response => {
                    const data = await response.json();
                    console.log('STATUS:', response.status, 'DATA:', data);
                    if (!response.ok) throw data;
                    return data;
                })
                .then(data => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-2xl' }
                    });
                })
                .catch(error => {
                    console.error('CAUGHT ERROR:', error);                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Deployment Failed',
                        text: error.message || 'A server error occurred.',
                        confirmButtonColor: '#0f766e',
                        customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl' }
                    });
                })
                .finally(() => {
                    this.loading = null;
                });
            }
        }
    }
</script>
@endsection