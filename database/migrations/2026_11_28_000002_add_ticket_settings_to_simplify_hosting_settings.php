<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The ticket settings, in the same row as the hoster's links. Tickets start off: a hoster turns them on. */
return new class extends Migration
{
    private const COLUMNS = ['tickets', 'tickets_without_server', 'tickets_auto_close', 'ticket_categories', 'ticket_intro'];

    public function up(): void
    {
        $missing = array_values(array_filter(self::COLUMNS, fn (string $column) => ! Schema::hasColumn('simplify_hosting_settings', $column)));
        if ($missing === []) {
            return;
        }
        Schema::table('simplify_hosting_settings', function (Blueprint $table) use ($missing) {
            foreach ($missing as $column) {
                match ($column) {
                    'tickets', 'tickets_auto_close' => $table->boolean($column)->default(false),
                    'tickets_without_server' => $table->boolean($column)->default(true),
                    'ticket_categories' => $table->string($column, 500)->nullable(),
                    default => $table->text($column)->nullable(),
                };
            }
        });
    }

    /** Uninstalling keeps the settings: installing again brings them back. */
    public function down(): void {}
};
