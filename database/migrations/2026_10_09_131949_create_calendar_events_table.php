
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();

            // meeting = undangan peserta
            // announcement = pengumuman perusahaan
            $table->string('event_type', 30)->default('meeting');

            $table->dateTime('start_at');
            $table->dateTime('end_at');

            // scheduled, cancelled
            $table->string('status', 30)->default('scheduled');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'start_at']);
            $table->index(['company_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};