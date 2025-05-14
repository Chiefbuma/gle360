<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loan', 'loan_id')->onDelete('cascade');
            $table->foreignId('payment_id')->constrained('payment', 'payment_id')->onDelete('cascade');
            $table->decimal('payment_amount', 15, 2);
            $table->date('date_paid')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};