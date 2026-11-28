<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support tickets and their messages. Plain id columns without foreign keys: Pelican's users and servers ids differ in
 * type between versions and databases, and a ticket of a deleted user or server simply shows it as deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('simplify_hosting_tickets')) {
            Schema::create('simplify_hosting_tickets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('server_id')->nullable()->index();
                $table->string('subject', 120);
                $table->string('category', 60)->nullable();
                $table->string('status', 20)->default('open')->index();
                $table->string('priority', 10)->default('normal');
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->boolean('staff_unread')->default(true);
                $table->boolean('customer_unread')->default(false);
                $table->timestamp('last_reply_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('simplify_hosting_ticket_messages')) {
            Schema::create('simplify_hosting_ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->boolean('staff')->default(false);
                $table->boolean('internal')->default(false);
                $table->text('body');
                $table->text('log')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /** Uninstalling keeps the tickets: installing again brings them back. */
    public function down(): void {}
};
