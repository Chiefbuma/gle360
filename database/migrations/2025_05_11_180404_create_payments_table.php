<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment', function (Blueprint $table) {
            $table->id('payment_id');
            $table->foreignId('loan_id')->constrained('loan', 'loan_id')->onDelete('cascade');
            $table->foreignId('borrower_id')->constrained('borrower', 'borrower_id')->onDelete('cascade');
            $table->decimal('payment_amount', 15, 2);
            $table->dateTime('payment_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};