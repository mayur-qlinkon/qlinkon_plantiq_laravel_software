@props([
    'callbackUrl',
    'name' => config('app.name', 'Qlinkon'),
    'description' => 'Secure Payment',
    'image' => '',
    'themeColor' => '#0f766e',
])

@pushOnce('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endPushOnce

{{-- The component sits invisibly and listens for the 'process-payment' event --}}
<div x-data="{
        handlePayment(data) {
            // data comes from the dispatched event!
            let options = {
                key: data.key_id,
                order_id: data.order_id,
                name: '{{ $name }}',
                description: '{{ $description }}',
                image: '{{ $image }}',
                theme: { color: '{{ $themeColor }}' },
                prefill: {
                    name: data.customer_name || '',
                    email: data.customer_email || '',
                    contact: data.customer_phone || ''
                },
                handler: (response) => {
                    // Populate the hidden form and submit it
                    this.$refs.payment_id.value = response.razorpay_payment_id;
                    this.$refs.order_id.value = response.razorpay_order_id;
                    this.$refs.signature.value = response.razorpay_signature;
                    this.$refs.internal_order.value = data.internal_order_number;
                    
                    this.$refs.razorpayForm.submit();
                }
            };

            let rzp = new window.Razorpay(options);
            
            rzp.on('payment.failed', (response) => {
                if (typeof BizAlert !== 'undefined') {
                    BizAlert.toast(response.error.description, 'error');
                } else {
                    alert('Payment Failed: ' + response.error.description);
                }
                // Optional: Dispatch an event back to the cart to re-enable the submit button
                window.dispatchEvent(new CustomEvent('payment-failed'));
            });

            rzp.open();
        }
    }"
    @process-payment.window="handlePayment($event.detail)"
>
    <form x-ref="razorpayForm" action="{{ $callbackUrl }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="razorpay_payment_id" x-ref="payment_id">
        <input type="hidden" name="razorpay_order_id" x-ref="order_id">
        <input type="hidden" name="razorpay_signature" x-ref="signature">
        <input type="hidden" name="order_number" x-ref="internal_order">
    </form>
</div>