<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenances', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Asset
            |--------------------------------------------------------------------------
            */
            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Vendor
            |--------------------------------------------------------------------------
            |
            | Vendor maintenance bisa berbeda dengan vendor pembelian asset.
            |
            */
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Maintenance Information
            |--------------------------------------------------------------------------
            */
            $table->date('maintenance_date');

            $table->string('maintenance_type', 50)
                ->nullable();

            $table->string('maintenance_title', 150)
                ->nullable();

            $table->text('description')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Technician / PIC
            |--------------------------------------------------------------------------
            */
            $table->string('technician_name', 150)
                ->nullable();

            $table->string('technician_phone', 30)
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Cost
            |--------------------------------------------------------------------------
            */
            $table->decimal('cost', 15, 2)
                ->default(0);


            /*
            |--------------------------------------------------------------------------
            | Result / Condition
            |--------------------------------------------------------------------------
            */
            $table->string('result', 50)
                ->nullable();

            $table->text('notes')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Next Maintenance
            |--------------------------------------------------------------------------
            |
            | Disimpan juga di histori untuk mengetahui jadwal
            | yang dihasilkan dari maintenance tersebut.
            |
            */
            $table->date('next_maintenance_date')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | scheduled = dijadwalkan
            | in_progress = sedang dikerjakan
            | completed = selesai
            | cancelled = dibatalkan
            |
            */
            $table->string('status', 30)
                ->default('completed');


            /*
            |--------------------------------------------------------------------------
            | Documents / Photos
            |--------------------------------------------------------------------------
            |
            | Nanti bisa menyimpan path JSON untuk beberapa file.
            |
            */
            $table->json('documents')
                ->nullable();

            $table->json('photos')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */
            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */
            $table->index([
                'company_id',
                'asset_id'
            ]);

            $table->index('maintenance_date');
            $table->index('next_maintenance_date');
            $table->index('status');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
    }
};