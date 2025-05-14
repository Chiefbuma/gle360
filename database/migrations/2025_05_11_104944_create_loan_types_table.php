<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_type', function (Blueprint $table) {
            $table->id('ltype_id');
            $table->text('ltype_name');
    
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_type');
    }
};