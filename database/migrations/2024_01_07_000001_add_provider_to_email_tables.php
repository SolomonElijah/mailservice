<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track which provider sent each email
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('provider', 20)->nullable()->after('status');
            // e.g. 'resend', 'ses', 'mailtrap'
        });

        // Allow per-campaign provider override
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('provider', 20)->nullable()->after('status');
        });

        // Allow per-scheduled-email provider override
        Schema::table('scheduled_emails', function (Blueprint $table) {
            $table->string('provider', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', fn($t) => $t->dropColumn('provider'));
        Schema::table('campaigns',  fn($t) => $t->dropColumn('provider'));
        Schema::table('scheduled_emails', fn($t) => $t->dropColumn('provider'));
    }
};
