<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simplify_hosting_settings', function (Blueprint $table) {
            $table->id();
            $table->string('billing_url')->nullable();
            $table->string('client_area_url')->nullable();
            $table->string('order_url')->nullable();
            $table->boolean('host_mode')->default(false);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simplify_hosting_settings');
    }
};
