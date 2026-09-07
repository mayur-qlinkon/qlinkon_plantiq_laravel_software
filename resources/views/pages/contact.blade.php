@extends('layouts.app')

@section('title', 'Contact Us - PlantIQ')

@section('content')
    {{-- ─── HERO SECTION ─── --}}
    <div class="bg-brand-900 py-20 relative overflow-hidden">
        {{-- Decorative background elements --}}
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none opacity-20">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-brand-500 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-10 w-72 h-72 bg-brand-400 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-4">Let's Talk</h1>
            <p class="text-lg text-brand-100 max-w-2xl mx-auto">
                Have questions about PlantIQ? We're here to help. Reach out to our team for support, inquiries, or partnership opportunities.
            </p>
        </div>
    </div>

    {{-- ─── MAIN CONTENT ─── --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24">
            
            {{-- LEFT COLUMN: GET IN TOUCH --}}
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-8">Get in Touch</h2>

                <div class="space-y-8">
                    {{-- Address --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 shadow-sm border border-brand-100">
                            <i class="fa-regular fa-building text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Address</h3>
                            <address class="text-gray-600 not-italic leading-relaxed">
                                Qlinkon Technology<br>
                                Block 79, Akashganga-2<br>
                                Madhuram Road<br>
                                Junagadh - 362015<br>
                                Gujarat, India
                            </address>
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 shadow-sm border border-brand-100">
                            <i class="fa-solid fa-phone text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Phone</h3>
                            <a href="tel:+919925180106" class="text-brand-600 hover:text-brand-700 font-medium transition-colors">
                                +91 9925180106
                            </a>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 shadow-sm border border-brand-100">
                            <i class="fa-regular fa-envelope text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">Email</h3>
                            <a href="mailto:plantiq@yahoo.com" class="text-brand-600 hover:text-brand-700 font-medium transition-colors">
                                plantiq@yahoo.com
                            </a>
                        </div>
                    </div>

                    {{-- WhatsApp --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center shrink-0 shadow-sm border border-green-100">
                            <i class="fa-brands fa-whatsapp text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">WhatsApp</h3>
                            <a href="https://wa.me/919925180106" target="_blank" rel="noopener noreferrer" class="text-green-600 hover:text-green-700 font-medium transition-colors inline-flex items-center gap-1.5">
                                Chat with us on WhatsApp <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                            </a>
                        </div>
                    </div>

                    {{-- Business Hours --}}
                    <div class="flex items-start gap-4 pt-4 border-t border-gray-100">
                        <div class="w-12 h-12 rounded-xl bg-gray-50 text-gray-500 flex items-center justify-center shrink-0 shadow-sm border border-gray-200">
                            <i class="fa-regular fa-clock text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Business Hours</h3>
                            <ul class="space-y-1.5 text-gray-600">
                                <li class="flex justify-between gap-8">
                                    <span>Monday - Saturday:</span> 
                                    <span class="font-medium text-gray-900">9:30 AM - 6:30 PM</span>
                                </li>
                               
                                <li class="flex justify-between gap-8">
                                    <span>Sunday:</span> 
                                    <span class="font-medium text-red-500">Closed</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: CONTACT FORM --}}
            <div>
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8 md:p-10">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Send us a Message</h2>

                    {{-- Success Alert --}}
                    @if (session('success'))
                        <div class="mb-8 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 text-sm px-5 py-4 rounded-xl shadow-sm">
                            <i class="fa-solid fa-circle-check text-green-600 text-xl shrink-0"></i>
                            <span class="font-medium">✓ Thank you! Your message has been sent successfully. We'll get back to you soon.</span>
                        </div>
                    @endif

                    <form action="" method="POST" class="space-y-6">
                        @csrf

                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" required placeholder="Your full name" value="{{ old('name') }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('name') border-red-300 ring-red-100 @enderror">
                            @error('name') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" required placeholder="your@email.com" value="{{ old('email') }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('email') border-red-300 ring-red-100 @enderror">
                            @error('email') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="+91 XXXXX XXXXX" value="{{ old('phone') }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('phone') border-red-300 ring-red-100 @enderror">
                            @error('phone') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-semibold text-gray-700 mb-2">Subject <span class="text-red-500">*</span></label>
                            <input type="text" id="subject" name="subject" required placeholder="What is this about?" value="{{ old('subject') }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all @error('subject') border-red-300 ring-red-100 @enderror">
                            @error('subject') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">Message <span class="text-red-500">*</span></label>
                            <textarea id="message" name="message" required rows="4" placeholder="Tell us how we can help you..."
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all resize-y @error('message') border-red-300 ring-red-100 @enderror">{{ old('message') }}</textarea>
                            @error('message') <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" 
                            class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-base py-3.5 px-6 rounded-lg shadow-md shadow-brand-500/20 transition-all flex items-center justify-center gap-2">
                            <span>Send Message</span>
                            <i class="fa-regular fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection