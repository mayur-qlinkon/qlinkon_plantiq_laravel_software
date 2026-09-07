<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razorpay Component Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="{{ asset('assets/js/alpinejs.min.js') }}"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    {{-- The Dummy UI --}}
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center" x-data>
        <div class="w-16 h-16 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        </div>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Checkout Test</h2>
        <p class="text-sm text-gray-500 mb-6 font-mono">Backend Order ID: {{ $orderId }}</p>

        {{-- Dispatch the event to our global component! --}}
        <button 
            @click="$dispatch('process-payment', {
                key_id: '{{ $keyId }}',
                order_id: '{{ $orderId }}',
                internal_order_number: '{{ $internalOrder }}',
                customer_name: 'Test Customer',
                customer_phone: '9999999999',
                customer_email: 'test@qlinkon.com'
            })"
            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-4 rounded-xl transition-colors shadow-md">
            Pay ₹1.00 Now
        </button>
    </div>

    {{-- 🌟 PILLAR 2: Our DRY Global Component 🌟 --}}
    <x-razorpay-checkout 
        callback-url="/razorpay-test/verify" 
        name="Qlinkon Sandbox" 
        description="Testing Global Event" 
    />
@stack('scripts')
</body>
</html>