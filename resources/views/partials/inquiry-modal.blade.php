<div
    x-data="inquiryForm"
    x-show="$store.ui.inquiryModal"
    style="display: none"
    class="fixed inset-0 z-[110] overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Modal Backdrop -->
    <div
        x-show="$store.ui.inquiryModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"
        @click="$store.ui.inquiryModal = false"
    ></div>

    <!-- Modal Panel -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div
            x-show="$store.ui.inquiryModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
        >
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-xl leading-6 font-semibold text-gray-900" id="modal-title">Make an Inquiry</h3>
                    <button
                        type="button"
                        @click="$store.ui.inquiryModal = false"
                        class="text-gray-400 transition-colors hover:text-gray-500"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitInquiry" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm leading-6 font-medium text-gray-900">Full Name</label>
                        <div class="mt-2">
                            <input
                                type="text"
                                id="name"
                                x-model="formData.name"
                                required
                                class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-black focus:ring-1 focus:ring-black focus:outline-none sm:text-sm sm:leading-6"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm leading-6 font-medium text-gray-900"
                            >Email Address</label
                        >
                        <div class="mt-2">
                            <input
                                type="email"
                                id="email"
                                x-model="formData.email"
                                required
                                class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-black focus:ring-1 focus:ring-black focus:outline-none sm:text-sm sm:leading-6"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm leading-6 font-medium text-gray-900"
                            >Phone Number (Optional)</label
                        >
                        <div class="mt-2">
                            <input
                                type="text"
                                id="phone"
                                x-model="formData.phone"
                                class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-black focus:ring-1 focus:ring-black focus:outline-none sm:text-sm sm:leading-6"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="message" class="block text-sm leading-6 font-medium text-gray-900">Message</label>
                        <div class="mt-2">
                            <textarea
                                id="message"
                                x-model="formData.message"
                                rows="4"
                                required
                                class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-black focus:ring-1 focus:ring-black focus:outline-none sm:text-sm sm:leading-6"
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            @click="$store.ui.inquiryModal = false"
                            :disabled="isSubmitting"
                            class="inline-flex w-full justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-300 transition-colors ring-inset hover:bg-gray-50 disabled:opacity-70 sm:w-auto"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="inline-flex w-full justify-center rounded-lg bg-black px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto"
                        >
                            <span x-show="!isSubmitting">Submit Inquiry</span>
                            <span x-show="isSubmitting" style="display: none">Sending...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Extracted Logic to Avoid Syntax & Parsing Errors -->
<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("inquiryForm", () => ({
            isSubmitting: false,
            formData: {
                name: "",
                email: "",
                phone: "",
                message: "",
            },
            async submitInquiry() {
                this.isSubmitting = true;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
                    const response = await fetch("{{ route('welcome.inquire') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": token,
                            Accept: "application/json",
                        },
                        body: JSON.stringify(this.formData),
                    });

                    const result = await response.json();

                    if (response.ok) {
                        this.$store.ui.inquiryModal = false;
                        this.formData = { name: "", email: "", phone: "", message: "" };

                        Swal.fire({
                            title: "Success!",
                            text: result.message || "Thank you! We will get back to you soon.",
                            icon: "success",
                            confirmButtonColor: "#10B981",
                        });
                    } else {
                        let errorMsg = result.message || "Something went wrong.";
                        if (result.errors) {
                            errorMsg = Object.values(result.errors).flat().join("\n");
                        }
                        Swal.fire({
                            title: "Error!",
                            text: errorMsg,
                            icon: "error",
                            confirmButtonColor: "#EF4444",
                        });
                    }
                } catch (error) {
                    console.error("Submission error:", error);
                    Swal.fire({
                        title: "Error!",
                        text: "A network error occurred. Please try again.",
                        icon: "error",
                        confirmButtonColor: "#EF4444",
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },
        }));
    });
</script>
