<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->string('event_type', 50)->default('status_changed')->after('to_status')->index();
            $table->string('visibility', 20)->default('customer')->after('event_type')->index();
            $table->json('metadata')->nullable()->after('note');
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 80)->index();
            $table->string('title', 190);
            $table->text('body')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('icon', 40)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'read_at', 'created_at'], 'app_notifications_user_read_created_idx');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete()->index();
            $table->string('event_type', 100)->index();
            $table->string('subject_type', 190)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['subject_type', 'subject_id'], 'audit_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('app_notifications');
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropIndex(['event_type']);
            $table->dropIndex(['visibility']);
            $table->dropColumn(['event_type', 'visibility', 'metadata']);
        });
    }
};
