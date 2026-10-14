<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_pending_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->string('action', 100);

            // Token random untuk confirmation
            $table->string('token', 64)->unique();

            // Data hasil parsing AI / preview
            $table->json('payload');

            $table->string('status', 30)->default('pending');

            $table->timestamp('expires_at');

            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'company_id',
                'status',
            ]);

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_pending_actions');
    }
};