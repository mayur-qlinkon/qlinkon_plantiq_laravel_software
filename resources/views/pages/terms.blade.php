@extends('layouts.app')

@section('title', 'Terms & Conditions - PlantIQ')

@section('content')
    {{-- ─── PAGE HEADER ─── --}}
    <div class="bg-gray-900 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl md:text-5xl font-extrabold text-white tracking-tight mb-3">Terms & Conditions</h1>
            <p class="text-gray-400 font-medium">Last updated: June 27, 2026</p>
        </div>
    </div>

    {{-- ─── MAIN CONTAINER ─── --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col lg:flex-row gap-12">
        
        {{-- LEFT SIDEBAR: QUICK NAVIGATION (Hidden on mobile, sticky on desktop) --}}
        <aside class="hidden lg:block w-72 shrink-0">
            <div class="sticky top-28 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                <h3 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider mb-4">Quick Navigation</h3>
                <nav class="space-y-2.5">
                    <a href="#services" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">1. Services</a>
                    <a href="#subscription" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">2. Subscription & Usage</a>
                    <a href="#eligibility" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">3. User Eligibility</a>
                    <a href="#responsibilities" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">4. Your Responsibilities</a>
                    <a href="#payments" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">5. Payments & Billing</a>
                    <a href="#data" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">6. Data & Ownership</a>
                    <a href="#warranty" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">7. Warranty Disclaimer</a>
                    <a href="#liability" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">8. Limitation of Liability</a>
                    <a href="#termination" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">9. Suspension & Termination</a>
                    <a href="#changes" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">10. Changes to Terms</a>
                    <a href="#jurisdiction" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">11. Jurisdiction & Disputes</a>
                    <a href="#contact" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">12. Contact Us</a>
                </nav>
            </div>
        </aside>

        {{-- RIGHT CONTENT AREA --}}
        <div class="flex-1 max-w-4xl bg-white rounded-2xl md:p-8 md:border md:border-gray-100 md:shadow-sm">
            
            {{-- Important Notice Box --}}
            <div class="bg-blue-50 border-l-4 border-blue-500 p-5 rounded-r-xl mb-10 shadow-sm">
                <div class="flex gap-3">
                    <i class="fa-solid fa-circle-info text-blue-500 text-xl shrink-0 mt-0.5"></i>
                    <p class="text-blue-800 font-medium leading-relaxed">
                        By accessing or using PlantIQ services, you agree to be bound by these Terms & Conditions. 
                        If you do not agree to these terms, please do not use our services.
                    </p>
                </div>
            </div>

            <div class="space-y-12 text-gray-600 leading-relaxed">
                
                {{-- SECTION 1 --}}
                <section id="services" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">1. Services</h2>
                    <p class="mb-4">PlantIQ is an AI-powered, cloud-based SaaS platform designed to help plant nurseries manage inventory, billing, customer relationships, and online sales operations. Our services include:</p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li>Digital inventory management system</li>
                        <li>Smart point-of-sale (POS) and billing capabilities</li>
                        <li>Customer relationship management (CRM)</li>
                        <li>E-commerce storefront for online plant sales</li>
                        <li>AI-powered plant guidance and recommendations</li>
                        <li>Analytics and business insights</li>
                        <li>Multi-location management (where applicable)</li>
                        <li>Customer support services</li>
                    </ul>
                </section>

                {{-- SECTION 2 --}}
                <section id="subscription" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">2. Subscription & Usage</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">2.1 Ownership</h3>
                    <p class="mb-4">PlantIQ is provided on a subscription basis with monthly or annual billing options. By subscribing, you receive a limited, non-exclusive, non-transferable right to use the platform for managing your plant nursery business.</p>
                    <p class="mb-4">Ownership of the PlantIQ software, source code, databases, and intellectual property remain exclusively with Qlinkon Technology. Your subscription grants you only the right to use the platform as permitted under these terms.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">2.2 Access Terms</h3>
                    <p class="mb-4">Your access to PlantIQ is personal to your business account and may not be shared, resold, or transferred to third parties without written consent from Qlinkon Technology.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">2.3 Acceptable Use</h3>
                    <p class="mb-4">You agree to use PlantIQ only for lawful purposes and in a way that does not infringe the rights of others or restrict their use and enjoyment of the platform. Prohibited conduct includes:</p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li>Harassing, threatening, or abusing any user or employee</li>
                        <li>Attempting to gain unauthorized access to the system</li>
                        <li>Introducing viruses, malware, or harmful code</li>
                        <li>Using the platform for illegal activities</li>
                        <li>Reverse engineering or attempting to derive the source code</li>
                        <li>Reselling access or services without authorization</li>
                    </ul>
                </section>

                {{-- SECTION 3 --}}
                <section id="eligibility" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">3. User Eligibility</h2>
                    <p class="mb-4">By using PlantIQ, you represent and warrant that:</p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li>You are at least 18 years old or have parental/guardian consent</li>
                        <li>You are authorized to enter into a binding agreement</li>
                        <li>Your business is lawful and complies with all applicable laws</li>
                        <li>You are not prohibited from using our services under Indian law or international sanctions</li>
                        <li>All information provided during registration is accurate and complete</li>
                    </ul>
                </section>

                {{-- SECTION 4 --}}
                <section id="responsibilities" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">4. Your Responsibilities</h2>
                    <p class="mb-4">As a PlantIQ user, you are responsible for:</p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li><strong>Account Security:</strong> Maintaining confidentiality of your login credentials and account access</li>
                        <li><strong>Accurate Information:</strong> Providing truthful, accurate, and complete business and billing information</li>
                        <li><strong>Lawful Use:</strong> Using PlantIQ only for lawful, legitimate business purposes</li>
                        <li><strong>Data Accuracy:</strong> Ensuring accuracy of inventory, customer, and transaction data entered into the system</li>
                        <li><strong>Timely Payment:</strong> Paying subscription fees by the due date as specified in your invoice</li>
                        <li><strong>Backup:</strong> Maintaining backup copies of critical business data</li>
                        <li><strong>Compliance:</strong> Complying with all applicable business, tax, and regulatory laws</li>
                        <li><strong>Unauthorized Activity:</strong> Notifying us immediately of any unauthorized access or suspicious activity</li>
                    </ul>
                </section>

                {{-- SECTION 5 --}}
                <section id="payments" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">5. Payments & Billing</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">5.1 Subscription Fees</h3>
                    <p class="mb-4">PlantIQ offers tiered subscription plans with varying feature sets and pricing. Monthly and annual billing options are available. Pricing is displayed clearly during signup and before purchase.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">5.2 Payment Processing</h3>
                    <p class="mb-4">Payments are processed through Cashfree Payments, a PCI-DSS compliant payment gateway. Your payment information is encrypted and secured. Qlinkon Technology does not store your complete credit card information.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">5.3 Billing Cycle</h3>
                    <p class="mb-4">Your subscription begins on the date of purchase and renews automatically on the same day each month or year, depending on your chosen plan. You will receive an invoice and payment reminder before each renewal.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">5.4 Non-Refundable Subscriptions</h3>
                    <p class="mb-4"><strong>IMPORTANT:</strong> All subscription fees are non-refundable. This is custom software configured specifically for your business. Once a billing period begins, payment is non-refundable even if you discontinue use.</p>
                    <p class="mb-4">For detailed information on refunds and cancellations, please refer to our <a href="{{ url('/refund-policy') }}" class="text-brand-600 hover:underline">Refunds & Cancellations Policy</a>.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">5.5 Late Payment & Suspension</h3>
                    <p class="mb-4">If payment is not received by the due date, we may:</p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li>Suspend your access to PlantIQ</li>
                        <li>Restrict data export functionality</li>
                        <li>Apply late fees as permitted by law</li>
                        <li>Terminate your account and delete data after 30 days of non-payment</li>
                    </ul>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">5.6 Price Changes</h3>
                    <p class="mb-4">Qlinkon Technology reserves the right to modify subscription pricing with 30 days' written notice. Price increases will take effect at your next renewal date. Continued use of PlantIQ after the notice period constitutes acceptance of new pricing.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">5.7 Add-ons & Extra Features</h3>
                    <p class="mb-4">Additional features, add-ons, and modules may be purchased separately. These purchases are also non-refundable and subject to these same terms.</p>
                </section>

                {{-- SECTION 6 --}}
                <section id="data" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">6. Data & Ownership</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">6.1 Your Data</h3>
                    <p class="mb-4">All business data you enter into PlantIQ—including inventory records, customer information, sales transactions, and analytics—remains your property. You retain all rights to your data.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">6.2 Data Usage</h3>
                    <p class="mb-4">Qlinkon Technology will not:</p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li>Share your business data with competitors</li>
                        <li>Sell your data to third parties</li>
                        <li>Use your data for marketing purposes without consent</li>
                        <li>Disclose customer information beyond what is necessary for service delivery</li>
                    </ul>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">6.3 Data Backup & Recovery</h3>
                    <p class="mb-4">While we maintain regular backups of your data, Qlinkon Technology is not responsible for data loss or corruption resulting from user action, system failure, or circumstances beyond our control. We recommend you maintain independent backups of critical business data.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">6.4 Data Retention Upon Termination</h3>
                    <p class="mb-4">Upon account termination, we will retain your data for 30 days to allow for data export and recovery. After 30 days, your data will be permanently deleted. For longer retention, contact us at <a href="mailto:plantiq@yahoo.com" class="text-brand-600 hover:underline">plantiq@yahoo.com</a>.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">6.5 Confidentiality</h3>
                    <p class="mb-4">For business relationships requiring additional data protection or confidentiality agreements, a separate Non-Disclosure Agreement (NDA) must be executed between both parties.</p>
                </section>

                {{-- SECTION 7 --}}
                <section id="warranty" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">7. Warranty Disclaimer</h2>
                    
                    <div class="bg-orange-50 border-l-4 border-orange-500 p-5 rounded-r-xl my-6 shadow-sm">
                        <div class="flex gap-3">
                            <i class="fa-solid fa-triangle-exclamation text-orange-500 text-xl shrink-0 mt-0.5"></i>
                            <p class="text-orange-900 font-bold">
                                DISCLAIMER: PlantIQ is provided on an 'AS IS' and 'AS AVAILABLE' basis without any warranties, express or implied.
                            </p>
                        </div>
                    </div>

                    <p class="mb-4">Qlinkon Technology makes no warranties regarding:</p>
                    <ul class="list-disc list-inside space-y-2 ml-2 mb-6">
                        <li>Fitness for a particular purpose</li>
                        <li>Merchantability or quality</li>
                        <li>Continuous, uninterrupted, or error-free operation</li>
                        <li>Accuracy of data calculations, reports, or AI recommendations</li>
                        <li>Compatibility with your hardware or software</li>
                        <li>Protection against hacking, data theft, or cyber attacks</li>
                    </ul>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">7.1 Service Availability</h3>
                    <p class="mb-4">While we strive to maintain 99% uptime, we do not guarantee that PlantIQ will be available 24/7. Scheduled maintenance and unforeseeable technical issues may cause temporary unavailability.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">7.2 AI Recommendations Disclaimer</h3>
                    <p class="mb-4">AI-powered features, including plant guidance and recommendations, are provided for informational purposes only. These features are not a substitute for professional horticultural advice. Qlinkon Technology is not liable for any consequences arising from reliance on AI recommendations.</p>
                </section>

                {{-- SECTION 8 --}}
                <section id="liability" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">8. Limitation of Liability</h2>
                    
                    <div class="bg-orange-50 border-l-4 border-orange-500 p-5 rounded-r-xl my-6 shadow-sm">
                        <div class="flex gap-3">
                            <i class="fa-solid fa-shield-halved text-orange-500 text-xl shrink-0 mt-0.5"></i>
                            <p class="text-orange-900 font-bold leading-relaxed">
                                TO THE FULLEST EXTENT PERMITTED BY LAW: Qlinkon Technology shall not be liable for any indirect, incidental, special, punitive, or consequential damages, including but not limited to loss of profits, revenue, data, goodwill, business opportunities, or anticipated savings.
                            </p>
                        </div>
                    </div>

                    <p class="mb-4">This limitation applies regardless of the cause of action—whether based on contract, tort, negligence, strict liability, or any other theory—and even if we have been advised of the possibility of such damages.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">8.1 Maximum Liability</h3>
                    <p class="mb-4">Our total liability to you for any claim arising from these terms or your use of PlantIQ shall not exceed the total fees paid by you in the 12 months preceding the claim.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">8.2 Exceptions</h3>
                    <p class="mb-4">These limitations do not apply to:</p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li>Gross negligence or willful misconduct by Qlinkon Technology</li>
                        <li>Violations of applicable law that cannot be excluded by law</li>
                        <li>Death or personal injury caused by our negligence</li>
                    </ul>
                </section>

                {{-- SECTION 9 --}}
                <section id="termination" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">9. Suspension & Termination</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">9.1 Termination by You</h3>
                    <p class="mb-4">You may terminate your PlantIQ account at any time by visiting your account settings or contacting us at <a href="mailto:plantiq@yahoo.com" class="text-brand-600 hover:underline">plantiq@yahoo.com</a>. Termination is effective immediately, though fees for the current billing period remain due and non-refundable.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">9.2 Termination by Us</h3>
                    <p class="mb-4">Qlinkon Technology may suspend or terminate your account immediately, without notice, if you:</p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li>Violate these Terms & Conditions</li>
                        <li>Engage in harassment, threats, or abusive conduct</li>
                        <li>Attempt unauthorized access or hacking</li>
                        <li>Fail to pay subscription fees for 30 days</li>
                        <li>Use the platform for illegal activities</li>
                        <li>Breach intellectual property rights or confidentiality</li>
                    </ul>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">9.3 Suspension for Maintenance or Safety</h3>
                    <p class="mb-4">We may suspend your access temporarily for security updates, maintenance, or to protect the platform from abuse. We will attempt to provide notice when possible.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">9.4 Effect of Termination</h3>
                    <p class="mb-4">Upon termination, your right to use PlantIQ ceases immediately. You remain liable for all unpaid fees. Data will be retained for 30 days and then permanently deleted.</p>
                </section>

                {{-- SECTION 10 --}}
                <section id="changes" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">10. Changes to These Terms</h2>
                    <p class="mb-4">Qlinkon Technology reserves the right to modify these Terms & Conditions at any time. Changes will be effective immediately upon posting to this page. Your continued use of PlantIQ after changes are posted constitutes your acceptance of the modified terms.</p>
                    <p class="mb-4">We recommend reviewing this page periodically to stay informed of any changes.</p>
                </section>

                {{-- SECTION 11 --}}
                <section id="jurisdiction" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">11. Jurisdiction & Disputes</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">11.1 Governing Law</h3>
                    <p class="mb-4">These Terms & Conditions are governed by and construed in accordance with the laws of India, without regard to its conflict of law principles.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">11.2 Dispute Resolution</h3>
                    <p class="mb-4">Any dispute, claim, or controversy arising from or relating to these terms or your use of PlantIQ shall be subject exclusively to the jurisdiction of the courts in Junagadh, Gujarat, India.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">11.3 Waiver of Rights</h3>
                    <p class="mb-4">By using PlantIQ, you irrevocably waive any objection to venue in Junagadh courts and any claim that such courts lack personal jurisdiction.</p>
                </section>

                {{-- SECTION 12 --}}
                <section id="contact" class="scroll-mt-28 pt-8">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">12. Contact Us</h2>
                    <p class="mb-6">Questions About These Terms?</p>

                    <div class="bg-blue-50 border border-blue-100 p-6 md:p-8 rounded-2xl shadow-sm flex flex-col md:flex-row gap-8 items-start">
                        <div class="flex-1">
                            <h3 class="font-extrabold text-gray-900 mb-2">Qlinkon Technology</h3>
                            <address class="text-gray-600 not-italic leading-relaxed mb-4">
                                Block 79, Akashganga-2<br>
                                Madhuram Road<br>
                                Junagadh - 362015<br>
                                Gujarat, India
                            </address>
                        </div>
                        <div class="flex-1 space-y-3">
                            <a href="mailto:plantiq@yahoo.com" class="flex items-center gap-3 text-gray-700 hover:text-brand-600 transition-colors">
                                <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0"><i class="fa-solid fa-envelope text-brand-600"></i></div>
                                plantiq@yahoo.com
                            </a>
                            <a href="tel:+919925180106" class="flex items-center gap-3 text-gray-700 hover:text-brand-600 transition-colors">
                                <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0"><i class="fa-solid fa-phone text-brand-600"></i></div>
                                +91 9925180106
                            </a>
                            <a href="https://wa.me/919925180106" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 text-gray-700 hover:text-green-600 transition-colors">
                                <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0"><i class="fa-brands fa-whatsapp text-green-500"></i></div>
                                Chat with us
                            </a>
                        </div>
                    </div>
                </section>
                
            </div>
        </div>
    </div>
@endsection