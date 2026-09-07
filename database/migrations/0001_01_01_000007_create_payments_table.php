<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();

            // ── THE KEY: polymorphic party ──
            $table->enum('party_type', [
                'supplier',
                'customer',
                'employee',
                'other',
            ])->nullable()->index();
            $table->unsignedBigInteger('party_id')->nullable()->index();

            // ── THE KEY: polymorphic reference ──
            // what document triggered this payment
            $table->string('paymentable_type')->nullable();          // App\Models\Purchase, App\Models\Sale
            $table->unsignedBigInteger('paymentable_id')->nullable();
            $table->index(['paymentable_type', 'paymentable_id']);

            $table->string('payment_number');              // PAY-2024-0001
            $table->unique(['company_id', 'payment_number']);
            $table->string('reference')->nullable();                 // cheque no / UTR / UPI ref
            $table->date('payment_date');

            $table->enum('type', ['sent', 'received'])->index();     // sent = paid to supplier, received = from customer

            $table->decimal('amount', 15, 2);
            $table->decimal('amount_received', 15, 2)->default(0);
            $table->decimal('change_returned', 15, 2)->default(0);

            $table->string('status', 30)->default('completed');

            $table->string('payment_for')->nullable();

            // Future safe
            $table->string('currency_code', 3)->default('INR');
            $table->decimal('exchange_rate', 10, 4)->default(1);

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'party_type', 'party_id']);
            $table->index(['company_id', 'type', 'payment_date']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
