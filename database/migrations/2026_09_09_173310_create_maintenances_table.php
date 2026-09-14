<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | COMPANY
            |--------------------------------------------------------------------------
            */

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE REQUEST
            |--------------------------------------------------------------------------
            |
            | Menghubungkan hasil maintenance dengan request yang diselesaikan.
            |
            | Nullable karena maintenance lama / maintenance manual tetap
            | bisa dibuat tanpa melalui Maintenance Request.
            |
            */

            $table->foreignId('maintenance_request_id')
                ->nullable()
                ->constrained('maintenance_requests')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | ASSET
            |--------------------------------------------------------------------------
            */

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | MAINTENANCE INFORMATION
            |--------------------------------------------------------------------------
            */

            $table->string('maintenance_code', 50);

            $table->enum('maintenance_type', [
                'preventive',
                'corrective',
                'inspection',
                'calibration',
            ]);

            $table->date('maintenance_date');

            $table->text('problem_description')
                ->nullable();

            $table->text('action_taken')
                ->nullable();

            $table->string('technician_name', 150)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | VENDOR
            |--------------------------------------------------------------------------
            */

            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | COST
            |--------------------------------------------------------------------------
            */

            $table->decimal('cost', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'scheduled',
                'in_progress',
                'completed',
                'cancelled',
            ])
                ->default('scheduled');

            /*
            |--------------------------------------------------------------------------
            | NEXT MAINTENANCE
            |--------------------------------------------------------------------------
            */

            $table->date('next_maintenance_date')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | NOTES
            |--------------------------------------------------------------------------
            */

            $table->text('notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | CREATED BY
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | TIMESTAMPS
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | INDEX & UNIQUE
            |--------------------------------------------------------------------------
            */

            $table->index([
                'company_id',
                'maintenance_request_id',
            ]);

            $table->unique([
                'company_id',
                'maintenance_code',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
