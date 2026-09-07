@extends('layouts.app')

@section('title', 'Privacy Policy - PlantIQ')

@section('content')
    {{-- ─── PAGE HEADER ─── --}}
    <div class="bg-gray-900 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl md:text-5xl font-extrabold text-white tracking-tight mb-3">Privacy Policy</h1>
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
                    <a href="#information-we-collect" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">1. Information We Collect</a>
                    <a href="#how-we-use" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">2. How We Use Your Information</a>
                    <a href="#data-ownership" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">3. Data Ownership & Control</a>
                    <a href="#data-sharing" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">4. Data Sharing & Third Parties</a>
                    <a href="#data-security" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">5. Data Security</a>
                    <a href="#data-retention" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">6. Data Retention</a>
                    <a href="#privacy-rights" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">7. Your Privacy Rights</a>
                    <a href="#cookies" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">8. Cookies & Tracking</a>
                    <a href="#payment-billing" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">9. Payment & Billing Information</a>
                    <a href="#childrens-privacy" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">10. Children's Privacy</a>
                    <a href="#compliance-legal" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">11. Compliance & Legal</a>
                    <a href="#policy-updates" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">12. Policy Updates</a>
                    <a href="#contact" class="block text-sm text-gray-600 hover:text-brand-600 hover:translate-x-1 transition-transform">13. Contact Us</a>
                </nav>
            </div>
        </aside>

        {{-- RIGHT CONTENT AREA --}}
        <div class="flex-1 max-w-4xl bg-white rounded-2xl md:p-8 md:border md:border-gray-100 md:shadow-sm">
            
            {{-- Intro Box --}}
            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-xl mb-10 shadow-sm">
                <div class="flex gap-3">
                    <i class="fa-solid fa-shield-halved text-blue-500 text-xl shrink-0 mt-0.5"></i>
                    <p class="text-blue-800 font-medium leading-relaxed">
                        At PlantIQ by Qlinkon Technology, we respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your data when you use our SaaS platform.
                    </p>
                </div>
            </div>

            <div class="space-y-12 text-gray-600 leading-relaxed">
                
                {{-- SECTION 1 --}}
                <section id="information-we-collect" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">1. Information We Collect</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">1.1 Information You Provide</h3>
                    <p class="mb-4 text-sm">During registration and use of PlantIQ, you may provide us with:</p>
                    
                    <div class="space-y-4 mb-6">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Personal Information:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Full name and business name</li>
                                <li>Email address and phone number</li>
                                <li>Business address and location details</li>
                                <li>Bank account information (for payment processing)</li>
                                <li>Tax identification numbers (GST, PAN, etc.)</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Business Data:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Plant inventory details (names, varieties, pricing)</li>
                                <li>Customer information (names, contact details, purchase history)</li>
                                <li>Sales transactions and invoices</li>
                                <li>Employee and staff information</li>
                                <li>Supplier and vendor details</li>
                                <li>Financial and billing records</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Account Information:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Username and password</li>
                                <li>Login credentials and authentication details</li>
                                <li>Account preferences and settings</li>
                                <li>Communication history and support tickets</li>
                            </ul>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 mt-8 mb-3">1.2 Information Collected Automatically</h3>
                    <p class="mb-4 text-sm">When you access PlantIQ, we automatically collect:</p>
                    
                    <div class="space-y-4 mb-6">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Technical Information:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Device information (IP address, browser type, operating system)</li>
                                <li>Usage data (features accessed, time spent, actions performed)</li>
                                <li>Session information and login timestamps</li>
                                <li>Error logs and crash reports</li>
                                <li>Cookies and tracking technologies</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Platform Activity:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Pages and features you use</li>
                                <li>Frequency and duration of use</li>
                                <li>Data you input and modifications made</li>
                                <li>Reports you generate</li>
                                <li>Support requests and inquiries</li>
                            </ul>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 mt-8 mb-3">1.3 Information from Third Parties</h3>
                    <p class="mb-2 text-sm">We may receive information about you from:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                        <li>Payment processors (Cashfree Payments) - transaction details</li>
                        <li>Email and SMS service providers - delivery confirmations</li>
                        <li>Cloud hosting providers - technical performance data</li>
                        <li>Analytics services - user behavior patterns</li>
                    </ul>
                </section>

                {{-- SECTION 2 --}}
                <section id="how-we-use" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">2. How We Use Your Information</h2>
                    <p class="mb-6 text-sm">PlantIQ uses the collected information for the following purposes:</p>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Service Delivery:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Providing access to PlantIQ software and features</li>
                                <li>Processing your subscription and billing</li>
                                <li>Delivering your customized software setup</li>
                                <li>Enabling you to store and manage your business data</li>
                                <li>Providing technical support and customer service</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Service Improvement:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Understanding how you use PlantIQ</li>
                                <li>Identifying and fixing technical issues</li>
                                <li>Developing new features and improvements</li>
                                <li>Analyzing usage patterns to enhance user experience</li>
                                <li>Personalizing your dashboard and recommendations</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Communication:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Sending subscription renewal reminders</li>
                                <li>Notifying you about service updates and maintenance</li>
                                <li>Responding to your inquiries and support requests</li>
                                <li>Sending important account notifications</li>
                                <li>Providing billing statements and invoices</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Legal & Security:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Complying with legal obligations and court orders</li>
                                <li>Preventing fraud, abuse, and unauthorized access</li>
                                <li>Protecting the security of PlantIQ and user data</li>
                                <li>Enforcing our Terms & Conditions</li>
                                <li>Investigating and resolving disputes</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Business Analytics:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Aggregated statistics about platform usage</li>
                                <li>Performance metrics and system health monitoring</li>
                                <li>Customer satisfaction analysis</li>
                                <li>Revenue and subscription analytics</li>
                            </ul>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 mt-8 mb-3">2.1 AI-Powered Features</h3>
                    <p class="mb-3 text-sm">PlantIQ includes AI-powered recommendations and features:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2 mb-4">
                        <li>AI plant guidance for nursery staff and customers</li>
                        <li>Smart business recommendations based on your data</li>
                        <li>Automated alerts and inventory suggestions</li>
                        <li>Predictive analytics for sales and trends</li>
                    </ul>
                    <p class="text-sm font-medium bg-gray-50 p-4 rounded-lg border border-gray-100">
                        These AI features use your business data to generate insights. The AI algorithms do not identify you personally - they work with anonymized patterns from your usage.
                    </p>
                </section>

                {{-- SECTION 3 --}}
                <section id="data-ownership" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">3. Data Ownership & Control</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">3.1 Your Business Data Ownership</h3>
                    <p class="mb-4 text-sm">All business data you enter into PlantIQ — including inventory records, customer information, sales data, and analytics — is and remains your exclusive property.</p>
                    <p class="mb-4 text-sm">You retain full ownership and control of all data. Qlinkon Technology does not claim any ownership rights to your business data.</p>
                    <p class="mb-2 text-sm font-bold text-gray-900">You have the right to:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2 mb-6">
                        <li>Access and download your data anytime</li>
                        <li>Export your data in standard formats (CSV, Excel)</li>
                        <li>Delete your data by terminating your subscription</li>
                        <li>Request data corrections or updates</li>
                        <li>Restrict how your data is used by us</li>
                    </ul>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">3.2 Data We Do NOT Sell or Share</h3>
                    <p class="mb-3 text-sm">Qlinkon Technology explicitly <strong>DOES NOT</strong>:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                        <li>Sell your personal or business data to third parties</li>
                        <li>Share your data with competitors</li>
                        <li>Use your data for marketing purposes without consent</li>
                        <li>Disclose customer information beyond what is necessary for service delivery</li>
                        <li>Profit from your data in any way</li>
                        <li>Transfer your data to unauthorized parties</li>
                    </ul>
                </section>

                {{-- SECTION 4 --}}
                <section id="data-sharing" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">4. Data Sharing & Third Parties</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">4.1 When We Share Data</h3>
                    <p class="mb-4 text-sm">We only share your data with trusted third-party service providers when necessary to deliver PlantIQ services:</p>
                    
                    <div class="space-y-4 mb-6">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Payment Processing:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Cashfree Payments: Processes your subscription payments</li>
                                <li>Your payment information is encrypted and never stored in full</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Cloud Hosting & Storage:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Cloud service providers store your data securely</li>
                                <li>All data is encrypted both in transit and at rest</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Communication Services:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Email providers: Send invoices and notifications</li>
                                <li>SMS providers: Send OTP and verification codes</li>
                                <li>WhatsApp Business API: Customer support communication</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Analytics & Monitoring:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Analytics tools monitor platform performance</li>
                                <li>No personal customer data is shared with analytics services</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Legal Compliance:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>We may share data with legal authorities, government agencies, or law enforcement when required by law or court order</li>
                            </ul>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 mt-8 mb-3">4.2 International Data Transfer</h3>
                    <p class="text-sm">Some of our service providers may be located outside India. By using PlantIQ, you acknowledge that your data may be transferred to, stored in, and processed in countries other than your country of residence. We ensure that all international transfers comply with applicable data protection laws.</p>
                </section>

                {{-- SECTION 5 --}}
                <section id="data-security" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">5. Data Security</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">5.1 Security Measures</h3>
                    <p class="mb-4 text-sm">Qlinkon Technology implements comprehensive security measures to protect your data:</p>

                    <div class="space-y-4 mb-6">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Technical Security:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>HTTPS/SSL encryption for all data transmission</li>
                                <li>Encrypted database storage for sensitive information</li>
                                <li>Regular security audits and vulnerability assessments</li>
                                <li>Firewalls and intrusion detection systems</li>
                                <li>Secure authentication and access controls</li>
                                <li>Regular backups to prevent data loss</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Operational Security:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Restricted access to data (need-to-know basis)</li>
                                <li>Employee confidentiality agreements</li>
                                <li>Security training for all staff</li>
                                <li>Secure deletion procedures for old data</li>
                                <li>Incident response and breach notification procedures</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Infrastructure Security:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Cloud hosting with PCI-DSS compliance</li>
                                <li>Redundant systems to prevent downtime</li>
                                <li>Regular software updates and security patches</li>
                                <li>DDoS protection and network monitoring</li>
                            </ul>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 mt-8 mb-3">5.2 Limitations</h3>
                    <p class="mb-3 text-sm">While we implement robust security measures, please note:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                        <li>No internet transmission is 100% secure</li>
                        <li>No security system is impenetrable</li>
                        <li>Your password security depends on you - never share your credentials</li>
                        <li>Public WiFi networks may pose security risks</li>
                        <li>You are responsible for maintaining account security</li>
                    </ul>
                </section>

                {{-- SECTION 6 --}}
                <section id="data-retention" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">6. Data Retention</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">6.1 Active Subscription</h3>
                    <p class="text-sm mb-6">While your subscription is active, we retain your business data in PlantIQ so you can access and manage it anytime.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">6.2 After Subscription Cancellation</h3>
                    <p class="mb-4 text-sm">Upon subscription cancellation or termination:</p>
                    
                    <div class="space-y-4 mb-6">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">30-Day Grace Period:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Your data is retained for 30 days after cancellation</li>
                                <li>You can export or access your data during this period</li>
                                <li>This allows time for backup and recovery</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">After 30 Days:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>All data is permanently deleted from our servers</li>
                                <li>Data cannot be recovered after deletion</li>
                                <li>Deletions are irreversible</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Extended Retention:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>For longer retention, contact plantiq@yahoo.com</li>
                                <li>Extended retention may incur additional fees</li>
                                <li>Written request required for retention beyond 30 days</li>
                            </ul>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 mt-8 mb-3">6.3 Backup & Redundancy</h3>
                    <p class="text-sm">Qlinkon Technology maintains regular backups of your data for disaster recovery. Backups are encrypted and stored securely. Backups follow the same retention schedule as active data.</p>
                </section>

                {{-- SECTION 7 --}}
                <section id="privacy-rights" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">7. Your Privacy Rights</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">7.1 Right to Access</h3>
                    <p class="text-sm mb-6">You have the right to access and download your personal and business data from PlantIQ anytime. Use the 'Export Data' feature in your account settings or contact us at plantiq@yahoo.com.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">7.2 Right to Correction</h3>
                    <p class="text-sm mb-6">You can update, correct, or modify your personal information anytime through your account settings. For business data corrections, use PlantIQ's editing features or contact our support team.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">7.3 Right to Deletion</h3>
                    <p class="text-sm mb-6">You can request deletion of your data by cancelling your subscription. Your data will be deleted after the 30-day grace period. This is subject to legal obligations that may require us to retain certain data.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">7.4 Right to Opt-Out</h3>
                    <p class="mb-3 text-sm">You can opt out of:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2 mb-3">
                        <li>Marketing emails and promotional communications</li>
                        <li>Service update notifications (though critical notifications cannot be disabled)</li>
                        <li>Analytics and tracking (some features may be limited)</li>
                    </ul>
                    <p class="text-sm mb-6">Use your account settings or email plantiq@yahoo.com to adjust preferences.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-2">7.5 Right to Data Portability</h3>
                    <p class="text-sm">You can export your data from PlantIQ in standard, machine-readable formats (CSV, Excel, JSON) anytime. This allows you to transfer your data to other platforms or services.</p>
                </section>

                {{-- SECTION 8 --}}
                <section id="cookies" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">8. Cookies & Tracking</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">8.1 Cookies</h3>
                    <p class="mb-3 text-sm">PlantIQ uses cookies to:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2 mb-6">
                        <li>Keep you logged in</li>
                        <li>Remember your preferences</li>
                        <li>Analyze usage patterns</li>
                        <li>Improve user experience</li>
                        <li>Prevent fraud and abuse</li>
                    </ul>

                    <div class="space-y-4 mb-6">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Essential Cookies (cannot be disabled):</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Session cookies for login functionality</li>
                                <li>Security cookies for account protection</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Optional Cookies (can be disabled):</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Analytics cookies</li>
                                <li>Preference cookies</li>
                                <li>Advertising cookies (if applicable)</li>
                            </ul>
                        </div>
                    </div>
                    <p class="text-sm mb-8">You can control cookies through your browser settings. Disabling cookies may affect some PlantIQ features.</p>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">8.2 Tracking Technologies</h3>
                    <p class="mb-3 text-sm">We may use tracking technologies to monitor:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2 mb-3">
                        <li>Pages visited and features used</li>
                        <li>Time spent on the platform</li>
                        <li>Geographic location (general)</li>
                        <li>Device information</li>
                    </ul>
                    <p class="text-sm">This data helps us improve PlantIQ and user experience.</p>
                </section>

                {{-- SECTION 9 --}}
                <section id="payment-billing" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">9. Payment & Billing Information</h2>
                    <p class="mb-4 text-sm">When you subscribe to PlantIQ, your payment information is handled as follows:</p>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Payment Processing:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Payments processed by Cashfree Payments (PCI-DSS compliant)</li>
                                <li>Credit card and banking details are NOT stored by us</li>
                                <li>Only transaction reference is stored in our system</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Billing Information:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Your billing address and invoice details are retained</li>
                                <li>Tax records (GST, PAN) are kept for compliance</li>
                                <li>Payment history is maintained for account verification</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Security:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>All payment transmissions are encrypted (HTTPS/SSL)</li>
                                <li>We never have access to your full card numbers</li>
                                <li>Payment disputes are handled by Cashfree</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">Retention:</h4>
                            <ul class="list-disc list-inside text-sm space-y-1 ml-2">
                                <li>Payment and billing records are kept for 7 years per legal requirements</li>
                                <li>You can access your billing history anytime in your account</li>
                            </ul>
                        </div>
                    </div>
                </section>

                {{-- SECTION 10 --}}
                <section id="childrens-privacy" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">10. Children's Privacy</h2>
                    <p class="text-sm mb-3">PlantIQ is designed for business users (18 years and older). We do not knowingly collect data from children under 18.</p>
                    <p class="text-sm mb-3">If you are under 18, PlantIQ is not intended for you. If we become aware that we have collected data from someone under 18, we will delete it promptly.</p>
                    <p class="text-sm">Parents or guardians concerned about a child's data should contact us immediately at <a href="mailto:plantiq@yahoo.com" class="text-brand-600 hover:underline">plantiq@yahoo.com</a>.</p>
                </section>

                {{-- SECTION 11 --}}
                <section id="compliance-legal" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">11. Compliance & Legal</h2>
                    
                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">11.1 Indian Data Protection Laws</h3>
                    <p class="mb-3 text-sm">PlantIQ complies with applicable Indian laws including:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2 mb-8">
                        <li>Information Technology Act, 2000</li>
                        <li>Information Technology Rules, 2011</li>
                        <li>Reasonable efforts to secure information as per IT Act Section 43A</li>
                        <li>Business Responsibility and Sustainability Reporting (BRSR) principles</li>
                    </ul>

                    <h3 class="text-lg font-bold text-gray-800 mt-6 mb-3">11.2 Court Orders & Legal Requests</h3>
                    <p class="mb-3 text-sm">We may disclose your information when required by:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2 mb-4">
                        <li>Court orders or legal process</li>
                        <li>Government requests from authorized agencies</li>
                        <li>Investigation of illegal activities</li>
                        <li>Protection of our legal rights or safety</li>
                        <li>Enforcement of our Terms & Conditions</li>
                    </ul>
                    <p class="text-sm font-medium">We will provide you notice of such requests unless legally prohibited.</p>
                </section>

                {{-- SECTION 12 --}}
                <section id="policy-updates" class="scroll-mt-28">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">12. Policy Updates</h2>
                    <p class="mb-3 text-sm">Qlinkon Technology may update this Privacy Policy periodically to reflect:</p>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-2 mb-4">
                        <li>Changes in our practices</li>
                        <li>New features or services</li>
                        <li>Legal or regulatory changes</li>
                        <li>Improved privacy practices</li>
                    </ul>
                    <p class="text-sm mb-3">Updates become effective immediately upon posting to this page. Continued use of PlantIQ after updates means you accept the new policy.</p>
                    <p class="text-sm">We recommend reviewing this page regularly. For major changes, we may notify you by email or in-app notification.</p>
                </section>

                {{-- SECTION 13 --}}
                <section id="contact" class="scroll-mt-28 pt-8">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-4 pb-2 border-b border-gray-100">13. Contact Us</h2>
                    <p class="mb-6">Questions or Concerns About Your Privacy?</p>

                    <div class="bg-blue-50 border border-blue-100 p-6 md:p-8 rounded-2xl shadow-sm flex flex-col md:flex-row gap-8 items-start">
                        <div class="flex-1 space-y-4">
                            <p class="text-blue-900 text-sm font-medium mb-2">For privacy inquiries, data requests, or concerns, contact:</p>
                            
                            <a href="mailto:plantiq@yahoo.com" class="flex items-center gap-3 text-blue-900 hover:text-brand-600 transition-colors font-medium">
                                <div class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0"><i class="fa-solid fa-envelope text-blue-600"></i></div>
                                <div>
                                    <span class="block text-xs uppercase tracking-wide text-blue-600 font-bold mb-0.5">Data Protection Officer</span>
                                    plantiq@yahoo.com
                                </div>
                            </a>
                            <a href="tel:+919925180106" class="flex items-center gap-3 text-blue-900 hover:text-brand-600 transition-colors font-medium">
                                <div class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0"><i class="fa-solid fa-phone text-blue-600"></i></div>
                                +91 9925180106
                            </a>
                            <a href="https://wa.me/919925180106" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 text-blue-900 hover:text-green-600 transition-colors font-medium">
                                <div class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0"><i class="fa-brands fa-whatsapp text-green-500 text-lg"></i></div>
                                Chat on WhatsApp
                            </a>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-extrabold text-blue-900 mb-4 uppercase tracking-wide text-xs">Business Details</h3>
                            <p class="text-blue-900 text-sm font-medium mb-1"><i class="fa-regular fa-clock w-5 text-blue-500"></i> Monday – Friday, 9:00 AM – 6:00 PM IST</p>
                            <div class="mt-4 flex items-start gap-2">
                                <i class="fa-regular fa-building w-5 text-blue-500 mt-1"></i>
                                <address class="text-blue-900 text-sm not-italic leading-relaxed font-medium">
                                    Qlinkon Technology<br>
                                    Block 79, Akashganga-2<br>
                                    Madhuram Road, Junagadh – 362015<br>
                                    Gujarat, India
                                </address>
                            </div>
                        </div>
                    </div>
                </section>
                
            </div>
        </div>
    </div>
@endsection