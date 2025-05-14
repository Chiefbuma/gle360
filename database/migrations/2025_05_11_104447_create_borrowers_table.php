<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrower', function (Blueprint $table) {
            $table->id('borrower_id');
            $table->string('name', 100)->unique(); // Unique for name or plate number
            $table->string('contact_no', 15)->unique(); // Unique for contact number
            $table->string('national_id', 20)->unique(); // Unique for national ID
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrower');
    }
};