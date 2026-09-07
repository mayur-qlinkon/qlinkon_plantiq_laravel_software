@extends('layouts.app')

@section('title', 'About Us - PlantIQ')

@section('content')

    {{-- ─── 1. HERO SECTION ─── --}}
    <section class="bg-[#f9f9f9] pt-20 pb-24 lg:pt-28 lg:pb-32 relative overflow-hidden">
        {{-- Decorative background elements --}}
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none opacity-40">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-[#2ecc71] rounded-full blur-[100px]"></div>
            <div class="absolute bottom-10 left-10 w-72 h-72 bg-emerald-300 rounded-full blur-[100px]"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 text-sm font-bold text-[#333] shadow-sm mb-6">
                <span class="w-2 h-2 rounded-full bg-[#2ecc71] animate-pulse"></span>
                Built by Qlinkon Technology
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-[#333] tracking-tight mb-6">
                About <span class="text-[#2ecc71]">PlantIQ</span>
            </h1>
            <p class="text-lg md:text-xl text-gray-600 max-w-2xl mx-auto mb-10 font-medium leading-relaxed">
                Empowering Plant Nurseries with Smart Technology. We build affordable, AI-powered software that simplifies operations and drives sustainable growth.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') ?? '#' }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-[#2ecc71] hover:bg-[#27ae60] text-white font-bold transition-all shadow-lg shadow-[#2ecc71]/30 hover:-translate-y-1">
                    Get Started Free
                </a>
                <a href="{{ url('/contact') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-white border-2 border-gray-200 text-[#333] hover:border-[#2ecc71] hover:text-[#2ecc71] font-bold transition-all">
                    Book a Demo
                </a>
            </div>
        </div>
    </section>

    {{-- ─── OPTIONAL: STATS BANNER ─── --}}
    <section class="bg-[#333] py-12 border-y border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center divide-x divide-gray-700">
                <div>
                    <p class="text-4xl font-black text-[#2ecc71] mb-1">500+</p>
                    <p class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Nurseries</p>
                </div>
                <div>
                    <p class="text-4xl font-black text-[#2ecc71] mb-1">1M+</p>
                    <p class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Plants Managed</p>
                </div>
                <div>
                    <p class="text-4xl font-black text-[#2ecc71] mb-1">50k+</p>
                    <p class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Invoices Generated</p>
                </div>
                <div>
                    <p class="text-4xl font-black text-[#2ecc71] mb-1">24/7</p>
                    <p class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Customer Support</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── 3 & 4. MISSION & ABOUT QLINKON (Split Layout) ─── --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-20 items-center">
                
                {{-- Our Mission --}}
                <div class="bg-[#f9f9f9] p-8 md:p-12 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 text-9xl opacity-5">🎯</div>
                    <h2 class="text-3xl font-extrabold text-[#333] mb-6 relative z-10">Our Mission</h2>
                    <p class="text-gray-600 leading-relaxed text-lg relative z-10">
                        At PlantIQ, we believe every plant nursery deserves access to world-class business management tools — regardless of size. 
                        Our mission is to empower nursery owners with affordable, AI-powered software that simplifies operations, improves customer experience, and drives sustainable growth.
                    </p>
                </div>

                {{-- About Qlinkon --}}
                <div>
                    <h2 class="text-3xl font-extrabold text-[#333] mb-6">About Qlinkon Technology</h2>
                    <div class="space-y-4 text-gray-600 leading-relaxed">
                        <p>
                            PlantIQ is developed and maintained by <strong>Qlinkon Technology</strong>, a software and digital marketing company based in Junagadh, Gujarat, India. 
                        </p>
                        <p>
                            With 7+ years of experience in building innovative business solutions for Indian MSMEs (Micro, Small, and Medium Enterprises), we specialize in creating affordable, user-friendly software that transforms how small businesses operate.
                        </p>
                        <p class="font-semibold text-[#333] border-l-4 border-[#2ecc71] pl-4 mt-6">
                            "Our team combines technical expertise with a deep understanding of Indian business challenges. We don't just build software — we build solutions that matter."
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ─── 5. WHY PLANTIQ WAS CREATED (Before / After) ─── --}}
    <section class="py-20 bg-[#f9f9f9]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold text-[#333] mb-4">Why PlantIQ Was Created</h2>
                <p class="text-gray-600 text-lg">Most available software was either too expensive or designed for other industries. Nurseries needed a purpose-built solution.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- The Problem --}}
                <div class="bg-white rounded-3xl p-8 border border-red-100 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-2 bg-red-400"></div>
                    <h3 class="text-xl font-bold text-[#333] mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-circle-xmark text-red-500"></i> The Old Way (Struggles)
                    </h3>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-xmark text-red-400 mt-1"></i> Manual inventory tracking and paper records</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-xmark text-red-400 mt-1"></i> Difficulty managing multiple plant varieties & pricing</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-xmark text-red-400 mt-1"></i> No online sales capability</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-xmark text-red-400 mt-1"></i> Lost customer information and sales history</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-xmark text-red-400 mt-1"></i> Time spent on repetitive billing and invoicing</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-xmark text-red-400 mt-1"></i> No way to analyze business performance</li>
                    </ul>
                </div>

                {{-- The Solution --}}
                <div class="bg-white rounded-3xl p-8 border border-[#2ecc71]/30 shadow-xl shadow-[#2ecc71]/5 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-2 bg-[#2ecc71]"></div>
                    <h3 class="text-xl font-bold text-[#333] mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-[#2ecc71]"></i> The PlantIQ Solution
                    </h3>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-check text-[#2ecc71] mt-1"></i> Smart inventory management designed for plants</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-check text-[#2ecc71] mt-1"></i> AI-powered plant guidance for staff and customers</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-check text-[#2ecc71] mt-1"></i> Easy online store setup for selling plants 24/7</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-check text-[#2ecc71] mt-1"></i> Powerful Customer Relationship Management (CRM)</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-check text-[#2ecc71] mt-1"></i> Lightning-fast professional billing and invoicing</li>
                        <li class="flex items-start gap-3 text-gray-600"><i class="fa-solid fa-check text-[#2ecc71] mt-1"></i> Affordable pricing that makes sense for nurseries</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── 6. WHAT MAKES PLANTIQ DIFFERENT ─── --}}
    <section class="py-20 bg-white border-y border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold text-[#333]">What Makes PlantIQ Different</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
                {{-- Card 1 --}}
                <div class="bg-white p-8 rounded-2xl hover:-translate-y-2 hover:shadow-xl hover:shadow-gray-200/50 transition-all duration-300 border border-gray-100 flex flex-col items-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#2ecc71]/10 flex items-center justify-center text-[#2ecc71] text-2xl mb-5 shadow-sm">
                        <i class="fa-solid fa-leaf"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[#333] mb-3">Built for Plant Nurseries</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Unlike generic business software, PlantIQ is purpose-built. Every feature is designed with your specific nursery workflows in mind.</p>
                </div>
                
                {{-- Card 2 --}}
                <div class="bg-white p-8 rounded-2xl hover:-translate-y-2 hover:shadow-xl hover:shadow-gray-200/50 transition-all duration-300 border border-gray-100 flex flex-col items-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#2ecc71]/10 flex items-center justify-center text-[#2ecc71] text-2xl mb-5 shadow-sm">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[#333] mb-3">Affordable Pricing</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Professional software shouldn't break the bank. Our plans are designed for nurseries of all sizes, from small local shops to large operations.</p>
                </div>

                {{-- Card 3 --}}
                <div class="bg-white p-8 rounded-2xl hover:-translate-y-2 hover:shadow-xl hover:shadow-gray-200/50 transition-all duration-300 border border-gray-100 flex flex-col items-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#2ecc71]/10 flex items-center justify-center text-[#2ecc71] text-2xl mb-5 shadow-sm">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[#333] mb-3">AI-Powered Features</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Get intelligent plant recommendations, automated insights, and smart business analytics powered by cutting-edge artificial intelligence.</p>
                </div>

                {{-- Card 4 --}}
                <div class="bg-white p-8 rounded-2xl hover:-translate-y-2 hover:shadow-xl hover:shadow-gray-200/50 transition-all duration-300 border border-gray-100 flex flex-col items-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#2ecc71]/10 flex items-center justify-center text-[#2ecc71] text-2xl mb-5 shadow-sm">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <h3 class="text-lg font-bold text-[#333] mb-3">Made for India</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Built by Indians for Indian businesses. We understand local market challenges, payment methods, and regional business practices.</p>
                </div>
            </div>

        </div>
    </section>

    {{-- ─── 8. OUR VALUES & 7. OUR PRODUCTS ─── --}}
    <section class="py-20 bg-[#f9f9f9]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">
                
                {{-- Core Values (Spans 8 cols) --}}
                <div class="lg:col-span-8">
                    <h2 class="text-3xl font-extrabold text-[#333] mb-8">Our Core Values</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center">
                            <i class="fa-solid fa-wand-magic-sparkles text-3xl text-[#2ecc71] mb-4"></i>
                            <h3 class="font-bold text-[#333] mb-2">Simplicity</h3>
                            <p class="text-sm text-gray-600">Business software should be easy to use. Every feature in PlantIQ is designed to be highly intuitive.</p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center">
                            <i class="fa-solid fa-hand-holding-dollar text-3xl text-[#2ecc71] mb-4"></i>
                            <h3 class="font-bold text-[#333] mb-2">Affordability</h3>
                            <p class="text-sm text-gray-600">Technology should be accessible to all. We price our software so small nurseries get enterprise tools.</p>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center">
                            <i class="fa-solid fa-headset text-3xl text-[#2ecc71] mb-4"></i>
                            <h3 class="font-bold text-[#333] mb-2">Support</h3>
                            <p class="text-sm text-gray-600">We're committed to your success. Our team provides robust support via phone, email, and WhatsApp.</p>
                        </div>
                    </div>
                </div>

                {{-- Product Suite (Spans 4 cols) --}}
                <div class="lg:col-span-4 bg-[#333] rounded-3xl p-8 text-white shadow-xl">
                    <h2 class="text-2xl font-extrabold mb-2 text-white">Our Product Suite</h2>
                    <p class="text-gray-400 text-sm mb-6">While PlantIQ is our flagship product, Qlinkon Technology develops solutions for multiple industries:</p>
                    
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#2ecc71]/20 text-[#2ecc71] flex items-center justify-center shrink-0"><i class="fa-solid fa-leaf"></i></div>
                            <div>
                                <p class="font-bold text-white text-sm">PlantIQ</p>
                                <p class="text-xs text-gray-400">Nursery Management</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center shrink-0"><i class="fa-solid fa-star-and-crescent"></i></div>
                            <div>
                                <p class="font-bold text-white text-sm">iAstro</p>
                                <p class="text-xs text-gray-400">Astrologer Management</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center shrink-0"><i class="fa-solid fa-store"></i></div>
                            <div>
                                <p class="font-bold text-white text-sm">myShop</p>
                                <p class="text-xs text-gray-400">Online Store Builder</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-orange-500/20 text-orange-400 flex items-center justify-center shrink-0"><i class="fa-solid fa-cubes"></i></div>
                            <div>
                                <p class="font-bold text-white text-sm">Softiq</p>
                                <p class="text-xs text-gray-400">All-in-one ERP/CRM</p>
                            </div>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </section>

    {{-- ─── 9. CONTACT INFORMATION ─── --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-[#f9f9f9] border border-gray-100 rounded-3xl p-8 md:p-12 text-center max-w-4xl mx-auto shadow-sm">
                <h2 class="text-3xl font-extrabold text-[#333] mb-8">Contact Information</h2>
                
                <h3 class="text-xl font-bold text-[#2ecc71] mb-4">Qlinkon Technology</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Block 79, Akashganga-2, Madhuram Road,<br>
                    Junagadh - 362015, Gujarat, India<br>
                    <span class="text-sm italic mt-2 block text-gray-500">Office locations: Junagadh, Rajkot, Ahmedabad</span>
                </p>

                <div class="flex flex-wrap justify-center gap-4 sm:gap-8">
                    <a href="tel:+919925180106" class="flex flex-col items-center gap-2 group">
                        <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-gray-400 group-hover:text-[#2ecc71] group-hover:shadow-md transition-all border border-gray-100">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <span class="text-sm font-bold text-[#333]">+91 9925180106</span>
                    </a>
                    <a href="mailto:hi@qlinkon.com" class="flex flex-col items-center gap-2 group">
                        <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-gray-400 group-hover:text-[#2ecc71] group-hover:shadow-md transition-all border border-gray-100">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <span class="text-sm font-bold text-[#333]">hi@qlinkon.com</span>
                    </a>
                    <a href="https://wa.me/919925180106" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group">
                        <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-gray-400 group-hover:text-green-500 group-hover:shadow-md transition-all border border-gray-100">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                        </div>
                        <span class="text-sm font-bold text-[#333]">WhatsApp Us</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── 10. CALL TO ACTION ─── --}}
    <section class="bg-[#333] py-20 relative overflow-hidden">
        <div class="absolute inset-0 bg-[#2ecc71] opacity-5 pattern-grid-lg"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-3xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">Ready to Transform Your Nursery?</h2>
            <p class="text-xl text-gray-400 mb-10 font-medium">Join hundreds of nurseries already growing with PlantIQ.</p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') ?? '#' }}" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-[#2ecc71] hover:bg-[#27ae60] text-white font-bold transition-all shadow-lg shadow-[#2ecc71]/20 text-lg">
                    Get Started
                </a>
                <a href="{{ url('/contact') }}" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-transparent border-2 border-[#2ecc71] text-[#2ecc71] hover:bg-[#2ecc71] hover:text-white font-bold transition-all text-lg">
                    Book a Demo
                </a>
            </div>
        </div>
    </section>

@endsection