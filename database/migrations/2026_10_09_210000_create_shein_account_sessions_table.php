<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shein_account_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->longText('encrypted_state');
            $table->boolean('is_enabled')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shein_account_sessions');
    }
};
