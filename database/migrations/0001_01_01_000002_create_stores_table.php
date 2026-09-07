<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            // --- 🏠 Core Branding ---
            $table->string('name');
            $table->string('slug');
            $table->unique(['company_id', 'slug']);
            // Custom domain: e.g. mystore.com
            $table->string('domain')->nullable()->unique();

            // Custom subdomain label: e.g. "hardik" → hardik.yourapp.com
            $table->string('subdomain', 100)->nullable()->unique();

            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('upi_id')->nullable();
            $table->string('logo')->nullable();
            $table->string('signature')->nullable();

            // --- 🇮🇳 Compliance & Regional ---
            $table->string('gst_number', 15)->nullable();
            $table->string('currency', 10)->default('INR');

            // --- 📍 Location ---
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->default('India');
            $table->foreignId('state_id')->nullable()->constrained('states');
            $table->decimal('office_lat', 10, 7)->nullable();
            $table->decimal('office_lng', 10, 7)->nullable();
            $table->unsignedInteger('gps_radius_meters')->nullable();

            // --- 🏦 Bank Details (Store Level Overrides) ---
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('branch_name')->nullable();

            // --- 📄 Billing Configuration (Store Level Overrides) ---
            $table->string('invoice_prefix', 10)->nullable();
            $table->string('quotation_prefix', 10)->nullable();
            $table->string('purchase_prefix', 10)->nullable();
            $table->integer('next_invoice_number')->nullable()->default(1);
            $table->string('default_tax_type')->nullable(); // e.g., 'cgst_sgst', 'igst'
            $table->string('default_payment_terms')->nullable(); // e.g., 'immediate', 'net_15'
            $table->boolean('round_off_amounts')->nullable();

            // --- 📝 Default Invoice Content (Store Level Overrides) ---
            $table->text('invoice_footer_note')->nullable();
            $table->text('invoice_terms')->nullable();

            // Public identity
            $table->string('tagline', 160)->nullable();
            $table->text('description')->nullable();

            // Public contact (store-specific override of company settings)
            $table->string('whatsapp', 20)->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();

            // SEO
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 300)->nullable();

            // Hours & Map
            $table->string('business_hours', 500)->nullable(); // "Mon-Sat: 9am-8pm"
            $table->string('map_embed_url')->nullable();

            // Public on/off (independent per store, not killing whole company)
            $table->boolean('storefront_enabled')->default(true);
            $table->boolean('is_primary')->default(false)->comment('If true, auto-route orders here first');

            // --- Status & Timestamps ---
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
