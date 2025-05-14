<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan', function (Blueprint $table) {
            $table->id('loan_id');
            $table->foreignId('ltype_id')->constrained('loan_type', 'ltype_id')->onDelete('cascade');
            $table->foreignId('borrower_id')->constrained('borrower', 'borrower_id')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->integer('duration');
            $table->decimal('total_loan', 15, 2);
            $table->decimal('daily_amount', 15, 2);
            $table->foreignId('lplan_id')->constrained('loan_plan', 'lplan_id')->onDelete('cascade');
            $table->tinyInteger('status')->default(0)->comment('0=Request, 1=Complete, 3=Overdue');
            $table->dateTime('date_released')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan');
    }
};