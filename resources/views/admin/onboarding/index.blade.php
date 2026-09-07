<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Set Up Your First Store</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif']
                    },
                    colors: {
                        brand: {
                            500: '#0f766e',
                            600: '#115e59',
                            700: '#134e4a'
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4" x-data="{ isSubmitting: false }">

    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-brand-500/5 blur-3xl"></div>
        <div class="absolute top-[60%] -right-[10%] w-[40%] h-[40%] rounded-full bg-brand-500/5 blur-3xl"></div>
    </div>

    <div class="w-full max-w-lg relative z-10">

        <div class="flex flex-col items-center justify-center mb-8">
            <div
                class="w-12 h-12 rounded-xl bg-brand-600 text-white flex items-center justify-center shadow-lg shadow-brand-500/30 mb-4">
                <i data-lucide="store" class="w-6 h-6"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Welcome to Qlinkon!</h1>
            <p class="text-sm text-gray-500 mt-1 text-center">Let's get your business up and running by setting up your
                primary store or headquarters.</p>
        </div>

        @if ($errors->any())
            <div
                class="bg-red-50 text-red-600 px-5 py-4 rounded-xl text-sm font-bold shadow-sm border border-red-100 mb-6">
                <div class="flex items-center gap-2 mb-2"><i data-lucide="alert-triangle" class="w-5 h-5"></i> Please
                    fix the following errors:</div>
                <ul class="list-disc list-inside pl-7 text-xs font-medium space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">

            <div class="px-6 py-5 border-b border-gray-50 bg-gray-50/50 flex items-center gap-3">
                <div
                    class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold text-sm">
                    1</div>
                <div>
                    <h2 class="text-sm font-bold text-gray-800">Primary Location Details</h2>
                    <p class="text-[11px] text-gray-500">You can add more stores later from your dashboard.</p>
                </div>
            </div>

            <form action="{{ route('admin.onboarding.store') }}" method="POST" @submit="isSubmitting = true">
                @csrf

                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Store / Branch Name <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="building-2"
                                class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" name="name" required placeholder="e.g. Downtown Headquarters"
                                class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-gray-300">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Contact Phone</label>
                        <div class="relative">
                            <i data-lucide="phone"
                                class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>

                            <input type="tel" name="phone" placeholder="Store phone number" maxlength="10"
                                pattern="[0-9]{10}" inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,10)"
                                class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-gray-300">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Street Address</label>
                            <input type="text" name="address" placeholder="123 Main Street"
                                class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-gray-300">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[12px] font-bold text-gray-700 mb-1.5">City</label>
                            <input type="text" name="city" placeholder="City name"
                                class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-gray-300">
                        </div>
                    </div>
                </div>

                <div class="p-6 pt-0 mt-2">
                    <button type="submit" :disabled="isSubmitting"
                        class="w-full bg-brand-600 hover:bg-brand-700 text-white py-3 rounded-xl text-sm font-bold transition-all shadow-md flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                        <template x-if="isSubmitting">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                        </template>
                        <span x-text="isSubmitting ? 'Setting up your workspace...' : 'Create Store & Continue'"></span>
                        <template x-if="!isSubmitting">
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </template>
                    </button>
                    <p class="text-center text-[11px] text-gray-400 mt-4">
                        <i data-lucide="lock" class="w-3 h-3 inline-block -mt-0.5 mr-1"></i> Your data is securely
                        isolated and encrypted.
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
