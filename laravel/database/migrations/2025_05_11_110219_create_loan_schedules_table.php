<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedule', function (Blueprint $table) {
            $table->id('loan_sched_id');
            $table->foreignId('loan_id')->constrained('loan', 'loan_id')->onDelete('cascade');
            $table->date('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedule');
    }
};