<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_plan', function (Blueprint $table) {
            $table->id('lplan_id');
            $table->float('lplan_interest');
            $table->integer('lplan_penalty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_plan');
    }
};