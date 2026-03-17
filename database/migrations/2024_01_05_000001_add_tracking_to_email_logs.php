<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add tracking token to email_logs (used for open pixel + click wrapping)
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('tracking_token', 64)->nullable()->unique()->after('id');
            $table->string('message_id')->nullable()->after('tracking_token'); // Resend message ID
        });

        // Dedicated click tracking table
        Schema::create('email_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_token', 64)->index();
            $table->foreignId('email_log_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_url', 2048);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        // Resend webhook events log
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');          // email.delivered, email.opened, email.bounced …
            $table->string('resend_message_id')->nullable()->index();
            $table->string('recipient_email')->nullable();
            $table->json('payload');               // raw JSON from Resend
            $table->boolean('processed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('email_clicks');
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn(['tracking_token', 'message_id']);
        });
    }
};
