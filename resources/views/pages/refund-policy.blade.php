@extends('layouts.app')

@section('title', 'Refunds & Cancellations - PlantIQ')

@section('content')
    {{-- ─── PAGE HEADER ─── --}}
    <div class="bg-gray-900 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl md:text-5xl font-extrabold text-white tracking-tight mb-3">Refunds & Cancellations</h1>
            <p class="text-gray-400 font-medium">Last updated: June 27, 2026</p>
        </div>
    </div>

    {{-- ─── MAIN CONTAINER ─── --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12 text-gray-700 leading-relaxed">

        {{-- ─── INTRO & ADVISORY ─── --}}
        <div class="space-y-6">
            {{-- Intro Box --}}
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6 shadow-sm">
                <h2 class="text-lg font-bold text-blue-900 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-blue-500"></i> Please Read Before Placing an Order
                </h2>
                <p class="text-blue-800 text-sm">
                    PlantIQ provides custom-configured software and specialty physical products built specifically for your nursery business. Because every setup is tailored to your requirements, our refund and return terms differ by product type. This page explains the full policy for each category — please read it carefully before subscribing or placing any order.
                </p>
            </div>

            {{-- Advisory Box --}}
            <div class="bg-orange-50 border-l-4 border-orange-500 rounded-r-2xl p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-orange-900 mb-3 flex items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-orange-500 mt-1"></i> 
                    <span>Important Advisory — Consult Before You Order</span>
                </h2>
                <div class="text-orange-900 text-sm space-y-3">
                    <p class="font-bold">Confused about which plan or product is right for you?</p>
                    <p>Please call or WhatsApp our team at <strong>+91 9925180106</strong> BEFORE placing your order. Our team will help you choose the correct subscription plan, plant tags, QR kit, or any accessory that matches your nursery's needs.</p>
                    <p class="font-black uppercase tracking-wide">ONCE AN ORDER IS PLACED, IT CANNOT BE CANCELLED, EXCHANGED, OR REFUNDED unless the specific exception conditions described on this page apply. We strongly recommend speaking with us first to avoid any confusion.</p>
                </div>
            </div>
        </div>

        <hr class="border-gray-200">

        {{-- ─── SECTION A: SOFTWARE SUBSCRIPTION ─── --}}
        <section id="section-a" class="scroll-mt-28">
            <span class="inline-block bg-gray-100 text-gray-500 text-xs font-black uppercase tracking-widest px-3 py-1 rounded mb-3">SECTION A</span>
            <h2 class="text-2xl font-extrabold text-gray-900 mb-6">Software Subscription — No Refund Policy</h2>

            <div class="bg-red-50 border-l-4 border-red-500 rounded-r-2xl p-6 shadow-sm mb-8">
                <p class="font-black text-red-900 uppercase tracking-wide mb-3">NO REFUNDS OR RETURNS ON SOFTWARE SUBSCRIPTIONS.</p>
                <div class="text-red-800 text-sm space-y-3">
                    <p>PlantIQ is not an off-the-shelf product. Your subscription includes custom configuration based on your nursery's specific requirements — including service features, module setup, business data entry, and initial system configuration. This customization begins immediately after your subscription is activated.</p>
                    <p class="font-bold">Because of this, NO REFUND, RETURN, OR CREDIT OF ANY KIND IS AVAILABLE ONCE A SUBSCRIPTION IS ACTIVATED, regardless of whether the plan is monthly or annual.</p>
                </div>
            </div>

            <h3 class="text-lg font-bold text-gray-900 mt-8 mb-3">Why No Refund?</h3>
            <p class="mb-3 text-sm">Your PlantIQ setup involves:</p>
            <ul class="list-disc list-inside space-y-1.5 ml-2 mb-6 text-sm">
                <li>Selection and activation of service features specific to your business</li>
                <li>Initial system configuration and module setup as per your requirements</li>
                <li>Basic data entry and onboarding effort performed by our team</li>
                <li>Custom access provisioning for your nursery account</li>
            </ul>
            <p class="text-sm italic text-gray-600 mb-8">Since this work is performed exclusively for your business from the moment of subscription, the fees paid are non-refundable under any circumstances after the cancellation window described below.</p>

            <h3 class="text-lg font-bold text-gray-900 mb-4">6-Hour Cancellation Window</h3>
            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 shadow-sm mb-6 text-center sm:text-left flex flex-col sm:flex-row items-center gap-6">
                <div class="shrink-0 bg-yellow-400 text-yellow-900 font-black text-3xl px-6 py-4 rounded-xl shadow-inner">
                    6 HOURS
                </div>
                <div>
                    <p class="text-yellow-900 font-bold mb-2">After subscribing, you have 6 hours to cancel your software subscription and/or Plant QR Kit printing order by contacting our team directly.</p>
                    <p class="text-sm text-yellow-800 mb-2">If you change your mind immediately after subscribing, you must:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-800 text-left space-y-1">
                        <li>Contact us within <strong>6 HOURS</strong> of your subscription activation</li>
                        <li>Reach us by <strong>PHONE CALL or EMAIL only</strong> — self-service cancellation is not available</li>
                        <li>Phone: <strong>+91 9925180106</strong></li>
                        <li>Email: <strong>plantiq@yahoo.com</strong></li>
                    </ul>
                </div>
            </div>

            <div class="bg-orange-50 text-orange-900 text-sm font-bold p-4 rounded-xl border border-orange-200 mb-8 flex items-center gap-3">
                <i class="fa-solid fa-clock text-orange-500 text-xl shrink-0"></i>
                <p>After 6 hours: No cancellation, no refund, and no exchange is available under any circumstances. The subscription fees are fully earned and will not be returned.</p>
            </div>

            <h3 class="text-lg font-bold text-gray-900 mb-2">Plan Cancellation (End of Billing Period)</h3>
            <p class="text-sm mb-6">You may choose to cancel your subscription renewal at any time to prevent future charges. Cancellation stops future billing but does <strong>NOT</strong> entitle you to a refund of fees already paid for the current or any past billing period.</p>

            <h3 class="text-lg font-bold text-gray-900 mb-2">Data After Subscription End</h3>
            <ul class="list-disc list-inside space-y-1.5 ml-2 text-sm">
                <li>Your business data (inventory, customers, sales) is retained for 30 days after your subscription ends</li>
                <li>During this window you may export your data</li>
                <li>After 30 days, all data is permanently deleted and cannot be recovered</li>
                <li>For extended retention contact <a href="mailto:plantiq@yahoo.com" class="text-brand-600 hover:underline">plantiq@yahoo.com</a></li>
            </ul>
        </section>

        <hr class="border-gray-200">

        {{-- ─── SECTION B: CUSTOM PRINTED TAGS ─── --}}
        <section id="section-b" class="scroll-mt-28">
            <span class="inline-block bg-gray-100 text-gray-500 text-xs font-black uppercase tracking-widest px-3 py-1 rounded mb-3">SECTION B</span>
            <h2 class="text-2xl font-extrabold text-gray-900 mb-6">Custom Printed Fiber Plant Tags (Sticks) — No Return Policy</h2>

            <div class="bg-red-50 border-l-4 border-red-500 rounded-r-2xl p-6 shadow-sm mb-8">
                <p class="font-black text-red-900 uppercase tracking-wide mb-3">NO RETURN OR REFUND ON CUSTOM PRINTED PLANT TAGS.</p>
                <p class="text-red-800 text-sm">Plant tags (fiber sticks) supplied by PlantIQ are <strong>CUSTOM-PRINTED ITEMS</strong> produced specifically for your nursery — with your plant names, QR codes, or branding as per your order. Because these are made-to-order products, they cannot be returned, exchanged, or refunded under any circumstances once printing is completed.</p>
            </div>

            <h3 class="text-lg font-bold text-gray-900 mb-2">No Warranty on Printed Tags</h3>
            <p class="text-sm mb-8">Printed plant sticks are supplied without any usage warranty. Wear, fading, or damage resulting from field use, weather, water exposure, or handling at your premises is not covered.</p>

            <h3 class="text-lg font-bold text-gray-900 mb-4">Exception: Damaged Tags Received on Delivery</h3>
            <div class="bg-green-50 border-l-4 border-green-500 rounded-r-2xl p-4 shadow-sm mb-6">
                <p class="text-green-900 text-sm font-medium"><i class="fa-solid fa-box-open mr-2"></i>Replacement (not refund) is available only if tags arrive physically damaged at the time of delivery. The following conditions must all be satisfied:</p>
            </div>

            <h4 class="text-base font-bold text-gray-800 mb-4 uppercase tracking-wide">Conditions for Replacement Claim</h4>
            
            <div class="space-y-4 mb-8">
                <div class="flex gap-4 p-4 border border-gray-100 rounded-xl bg-white shadow-sm">
                    <div class="w-8 h-8 shrink-0 bg-brand-100 text-brand-600 font-black rounded-full flex items-center justify-center">1</div>
                    <div>
                        <p class="font-bold text-gray-900 mb-1">Record Unboxing Video</p>
                        <p class="text-sm text-gray-600">You must record a clear, uninterrupted video of the parcel being opened (unboxing) at the time of delivery. This video serves as proof of the condition in which the package was received. Claims without an unboxing video will not be accepted.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 border border-gray-100 rounded-xl bg-white shadow-sm">
                    <div class="w-8 h-8 shrink-0 bg-brand-100 text-brand-600 font-black rounded-full flex items-center justify-center">2</div>
                    <div>
                        <p class="font-bold text-gray-900 mb-1">Report Damage Immediately</p>
                        <p class="text-sm text-gray-600">Contact our team within 48 hours of delivery at +91 9925180106 or plantiq@yahoo.com. Share the unboxing video and photos of damaged tags. Claims raised after 48 hours of delivery will not be entertained.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 border border-gray-100 rounded-xl bg-white shadow-sm">
                    <div class="w-8 h-8 shrink-0 bg-brand-100 text-brand-600 font-black rounded-full flex items-center justify-center">3</div>
                    <div>
                        <p class="font-bold text-gray-900 mb-1">Return Damaged Tags via Courier</p>
                        <p class="text-sm text-gray-600">After our team confirms the damage claim, you will be required to send the damaged tags back to us via courier. <strong>RETURN COURIER CHARGES ARE TO BE BORNE BY THE CLIENT.</strong> Please use a tracked courier service and share the tracking details with us.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 border border-gray-100 rounded-xl bg-white shadow-sm">
                    <div class="w-8 h-8 shrink-0 bg-brand-100 text-brand-600 font-black rounded-full flex items-center justify-center">4</div>
                    <div>
                        <p class="font-bold text-gray-900 mb-1">Replacement Dispatched</p>
                        <p class="text-sm text-gray-600">Once we receive the returned damaged tags and verify the claim, we will dispatch replacement tags to your address. Replacement dispatch typically takes 5–7 working days after receipt of returned goods.</p>
                    </div>
                </div>
            </div>

            <div class="bg-orange-50 text-orange-900 text-sm p-4 rounded-xl border border-orange-200">
                <p class="font-bold mb-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i> IMPORTANT:</p>
                <p>This process provides REPLACEMENT ONLY — not a monetary refund. Return courier charges are the client's responsibility. No claim will be accepted without a valid unboxing video recorded at the time of delivery.</p>
            </div>
        </section>

        <hr class="border-gray-200">

        {{-- ─── SECTION C: THIRD-PARTY PRODUCTS ─── --}}
        <section id="section-c" class="scroll-mt-28">
            <span class="inline-block bg-gray-100 text-gray-500 text-xs font-black uppercase tracking-widest px-3 py-1 rounded mb-3">SECTION C</span>
            <h2 class="text-2xl font-extrabold text-gray-900 mb-6">Third-Party Physical Products — Arranged Only</h2>

            <div class="bg-red-50 border-l-4 border-red-500 rounded-r-2xl p-6 shadow-sm mb-8">
                <p class="font-black text-red-900 uppercase tracking-wide mb-3">PLANTIQ IS NOT THE MANUFACTURER OF THESE PRODUCTS.</p>
                <p class="text-red-800 text-sm">Products such as pocket printers, label printers, Bluetooth accessories, or any other hardware arranged on client request are THIRD-PARTY PRODUCTS. Qlinkon Technology / PlantIQ acts only as a procurement facilitator — we source and arrange delivery of these products at your request. We do not manufacture, brand, or warrant these products ourselves.</p>
            </div>

            <h3 class="text-lg font-bold text-gray-900 mb-4">Warranty & Guarantee Responsibility</h3>
            
            <div class="overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Aspect</th>
                            <th scope="col" class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Responsibility</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Product Quality</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">Third-party manufacturer / brand</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Warranty Claims</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">Third-party manufacturer / brand</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Manufacturing Defects</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">Third-party manufacturer / brand</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Repairs / Servicing</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">Third-party manufacturer / brand</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Procurement & Delivery</td>
                            <td class="px-6 py-4 whitespace-nowrap text-brand-600 font-bold">PlantIQ (Qlinkon Technology)</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Refund / Return</td>
                            <td class="px-6 py-4 whitespace-nowrap text-red-600 font-bold">Not available from PlantIQ</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-sm font-medium mb-3">By requesting procurement of a third-party product, you acknowledge and agree that:</p>
            <ul class="list-disc list-inside space-y-1.5 ml-2 text-sm text-gray-600">
                <li>PlantIQ has no liability for product quality, performance, or defects</li>
                <li>All warranty and guarantee claims must be directed to the product manufacturer or brand</li>
                <li>PlantIQ will not process any refund or accept any return for these products</li>
                <li>Any after-sales support is the manufacturer's responsibility alone</li>
            </ul>
        </section>

        <hr class="border-gray-200">

        {{-- ─── SECTION D: QR PRINTING KIT ─── --}}
        <section id="section-d" class="scroll-mt-28">
            <span class="inline-block bg-gray-100 text-gray-500 text-xs font-black uppercase tracking-widest px-3 py-1 rounded mb-3">SECTION D</span>
            <h2 class="text-2xl font-extrabold text-gray-900 mb-6">Plant QR Printing Kit — No Warranty on Usage</h2>

            <div class="bg-red-50 border-l-4 border-red-500 rounded-r-2xl p-6 shadow-sm mb-6">
                <p class="font-black text-red-900 uppercase tracking-wide mb-2">NO WARRANTY PROVIDED FOR ON-SITE USAGE.</p>
                <p class="text-red-800 text-sm">The Plant QR Printing Kit supplied by PlantIQ — including any printer accessories, QR label materials, ink, or related components — carries NO WARRANTY FOR USAGE AT YOUR PREMISES.</p>
            </div>

            <p class="text-sm font-medium mb-3">The following are not covered under any warranty or replacement claim:</p>
            <ul class="list-disc list-inside space-y-1.5 ml-2 text-sm text-gray-600 mb-8">
                <li>Damage caused during setup, installation, or day-to-day use at your nursery</li>
                <li>Wear and tear from regular printing operations</li>
                <li>Ink, paper, or consumable depletion from use</li>
                <li>Malfunctions arising from incorrect handling, improper storage, or use outside recommended conditions</li>
                <li>Damage caused by power fluctuations or electrical issues at the client site</li>
                <li>Software compatibility issues with non-PlantIQ systems</li>
            </ul>

            <div class="bg-orange-50 text-orange-900 text-sm p-4 rounded-xl border border-orange-200">
                <p class="font-bold mb-1"><i class="fa-solid fa-circle-exclamation mr-1"></i> NOTE:</p>
                <p>If the QR printing kit is ordered in combination with a software subscription, the 6-hour cancellation window (described in Section A) also applies to the kit order. After 6 hours, the kit order is confirmed and non-cancellable.</p>
            </div>
        </section>

        <hr class="border-gray-200">

        {{-- ─── QUICK REFERENCE TABLE ─── --}}
        <section id="quick-reference" class="scroll-mt-28">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-6">Quick Reference — Policy Summary</h2>
            
            <div class="overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left font-bold text-gray-900">Product / Service</th>
                            <th scope="col" class="px-6 py-3 text-left font-bold text-gray-900">Refund Available?</th>
                            <th scope="col" class="px-6 py-3 text-left font-bold text-gray-900">Return Available?</th>
                            <th scope="col" class="px-6 py-3 text-left font-bold text-gray-900">Cancellation Window</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">Software Subscription</td>
                            <td class="px-6 py-4 text-red-600 font-bold">❌ No</td>
                            <td class="px-6 py-4 text-red-600 font-bold">❌ No</td>
                            <td class="px-6 py-4 text-green-600 font-bold">✅ 6 hours (call/email only)</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">Custom Printed Plant Tags</td>
                            <td class="px-6 py-4 text-red-600 font-bold">❌ No</td>
                            <td class="px-6 py-4 text-yellow-600 font-bold max-w-xs">⚠️ Replacement only (if damaged on delivery with unboxing video)</td>
                            <td class="px-6 py-4 text-red-600 font-bold">❌ Not applicable</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">3rd Party Products<br><span class="text-xs text-gray-500 font-normal">(Pocket Printer etc.)</span></td>
                            <td class="px-6 py-4 text-red-600 font-bold">❌ No <span class="text-xs text-gray-500 font-normal">(PlantIQ's side)</span></td>
                            <td class="px-6 py-4 text-red-600 font-bold">❌ No <span class="text-xs text-gray-500 font-normal">(PlantIQ's side)</span></td>
                            <td class="px-6 py-4 text-red-600 font-bold">❌ Not applicable</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">Plant QR Printing Kit</td>
                            <td class="px-6 py-4 text-red-600 font-bold">❌ No</td>
                            <td class="px-6 py-4 text-red-600 font-bold">❌ No</td>
                            <td class="px-6 py-4 text-green-600 font-bold max-w-xs">✅ Within 6-hour window along with subscription</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <hr class="border-gray-200">

        {{-- ─── FAQ SECTION ─── --}}
        <section id="faq" class="scroll-mt-28">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-8">Frequently Asked Questions</h2>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-2">Can I get a refund if I'm not happy with PlantIQ after using it?</h3>
                    <p class="text-sm text-gray-600">No. PlantIQ is a custom-configured software platform. Your subscription activates a setup specifically built for your nursery business — including feature selection, module activation, and initial data configuration. Since this work begins immediately, no refund is available after the 6-hour cancellation window has passed. We encourage you to speak with our team and get a demo before subscribing to make an informed decision.</p>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-2">I subscribed by mistake. Can I cancel?</h3>
                    <p class="text-sm text-gray-600">Yes — but only within 6 hours of subscribing. You must contact our team directly by phone call or email within that window. After 6 hours, the subscription is confirmed and non-refundable. Call us at +91 9925180106 or email plantiq@yahoo.com immediately.</p>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-2">My plant tags arrived damaged. What do I do?</h3>
                    <p class="text-sm text-gray-600">If your plant tags arrived physically damaged, you are eligible for a replacement (not a refund) provided you: (1) have a clear unboxing video recorded at the time of delivery as proof, (2) report the issue within 48 hours of delivery, and (3) return the damaged tags via courier at your own cost. Once we receive and verify the returned tags, we'll dispatch replacements within 5–7 working days. Contact us immediately at +91 9925180106 or plantiq@yahoo.com.</p>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-2">My pocket printer stopped working. Will PlantIQ fix it?</h3>
                    <p class="text-sm text-gray-600">Pocket printers and similar accessories are third-party products that PlantIQ arranges on client request — they are not our own manufactured products. All warranty, servicing, and repair responsibility rests with the product's manufacturer or brand. PlantIQ does not provide any warranty or after-sales service for third-party hardware. Please contact the manufacturer directly for warranty claims.</p>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-2">Can I return the Plant QR Printing Kit if I don't use it?</h3>
                    <p class="text-sm text-gray-600">No. The Plant QR Printing Kit is a non-returnable product. No warranty is provided for usage at your premises. If the kit is ordered alongside a software subscription, you may cancel within the 6-hour window only. After that, the order is confirmed and cannot be returned or refunded.</p>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-2">Can I cancel my plan to stop future charges?</h3>
                    <p class="text-sm text-gray-600">Yes. You can cancel your subscription renewal at any time to prevent future billing cycles. However, this does not entitle you to a refund for fees already paid for the current or any past period. Your access to PlantIQ continues until the end of your current billing period. Contact us at plantiq@yahoo.com or call +91 9925180106 to process a cancellation of renewal.</p>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-2">I'm not sure which plan is right for my nursery. What should I do?</h3>
                    <p class="text-sm text-gray-600">Please call or WhatsApp us at +91 9925180106 before ordering. Our team will understand your nursery's size, needs, and workflow and recommend the right plan, physical kit, and accessories. Since no refunds are available after the 6-hour window, we strongly encourage consulting our team first. This is the best way to ensure you get exactly what your business needs.</p>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-2">What happens to my data when I stop my subscription?</h3>
                    <p class="text-sm text-gray-600">After your subscription ends, your business data (inventory, customer records, sales history) is retained on our servers for 30 days. During this window you can export your data. After 30 days, all data is permanently and irrecoverably deleted. If you need longer retention, contact us at plantiq@yahoo.com before your subscription ends.</p>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-2">Will I receive a refund if there is a technical error during payment?</h3>
                    <p class="text-sm text-gray-600">If your account was debited but the subscription was not activated due to a genuine payment gateway error, contact us immediately at plantiq@yahoo.com with your transaction ID and bank statement. We will investigate with our payment partner (Cashfree Payments) and process a refund for such duplicate or failed transactions. This is the only exception where a refund may be processed, and it is subject to verification.</p>
                </div>
            </div>
        </section>

        <hr class="border-gray-200">

        {{-- ─── CONTACT SUPPORT ─── --}}
        <section id="contact-support" class="scroll-mt-28">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-4">Questions About This Policy?</h2>
            <p class="mb-6">If you have any questions before subscribing or placing an order — please reach out to us first. We are happy to guide you.</p>

            <div class="bg-blue-50 border border-blue-100 p-6 md:p-8 rounded-2xl shadow-sm flex flex-col md:flex-row gap-8 items-start">
                <div class="flex-1 space-y-4">
                    <h3 class="font-extrabold text-blue-900 mb-2 uppercase tracking-wide text-xs">Reach Out To Us</h3>
                    <a href="tel:+919925180106" class="flex items-center gap-3 text-blue-900 hover:text-brand-600 transition-colors font-medium">
                        <div class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0"><i class="fa-solid fa-phone text-blue-600"></i></div>
                        +91 9925180106
                    </a>
                    <a href="https://wa.me/919925180106" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 text-blue-900 hover:text-green-600 transition-colors font-medium">
                        <div class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0"><i class="fa-brands fa-whatsapp text-green-500 text-lg"></i></div>
                        Chat with us on WhatsApp
                    </a>
                    <a href="mailto:plantiq@yahoo.com" class="flex items-center gap-3 text-blue-900 hover:text-brand-600 transition-colors font-medium">
                        <div class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center shrink-0"><i class="fa-solid fa-envelope text-blue-600"></i></div>
                        plantiq@yahoo.com
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

        <hr class="border-gray-200">

        {{-- ─── POLICY UPDATES ─── --}}
        <section id="policy-updates" class="scroll-mt-28 mb-12">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-4">Policy Updates</h2>
            <p class="text-sm">Qlinkon Technology reserves the right to modify this Refunds & Cancellations Policy at any time. Changes take effect immediately upon posting to this page. Continued use of PlantIQ after any changes constitutes your acceptance of the updated policy. We recommend checking this page periodically.</p>
        </section>

    </div>
@endsection