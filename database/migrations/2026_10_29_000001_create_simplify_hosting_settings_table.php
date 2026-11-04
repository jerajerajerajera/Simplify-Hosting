<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uninstalling keeps the table (down() below), so installing again finds it with the hoster's settings.
        if (Schema::hasTable('simplify_hosting_settings')) {
            return;
        }
        Schema::create('simplify_hosting_settings', function (Blueprint $table) {
            $table->id();
            $table->string('billing_url')->nullable();
            $table->string('client_area_url')->nullable();
            $table->string('order_url')->nullable();
            $table->boolean('host_mode')->default(false);
            $table->timestamp('updated_at')->nullable();
        });
    }

    /** Uninstalling keeps the hoster's settings: installing again brings them back. */
    public function down(): void {}
};
