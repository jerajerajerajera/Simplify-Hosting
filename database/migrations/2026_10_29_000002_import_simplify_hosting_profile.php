<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Simplify 1.0 kept the billing link and "a hosting company" in its own settings; Hosting takes them over once. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('simplify_settings') || DB::table('simplify_hosting_settings')->exists()) {
            return;
        }
        $old = DB::table('simplify_settings')->whereIn('key', ['profile', 'billing_url'])->pluck('value', 'key')
            ->map(fn ($value) => json_decode((string) $value, true));
        $billing = is_string($old['billing_url'] ?? null) && preg_match('#^https://[^\s<>"]+$#', $old['billing_url']) ? $old['billing_url'] : null;
        $hosting = ($old['profile'] ?? null) === 'hosting';
        if ($hosting || $billing !== null) {
            DB::table('simplify_hosting_settings')->insert([
                'id' => 1,
                'billing_url' => $hosting ? $billing : null,
                'host_mode' => $hosting,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void {}
};
