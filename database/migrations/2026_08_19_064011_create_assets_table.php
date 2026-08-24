<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {

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
            | Category
            |--------------------------------------------------------------------------
            */
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Sub Category - Optional
            |--------------------------------------------------------------------------
            */
            $table->foreignId('sub_category_id')
                ->nullable()
                ->constrained('sub_categories')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Vendor - Optional
            |--------------------------------------------------------------------------
            */
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Responsible User - Optional
            |--------------------------------------------------------------------------
            |
            | User Inventa yang bertanggung jawab atas asset.
            |
            */
            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Asset Identity
            |--------------------------------------------------------------------------
            */
            $table->string('asset_code', 50);
            $table->string('asset_name');
            $table->string('brand', 100)->nullable();
            $table->string('model', 150)->nullable();
            $table->string('serial_number', 150)->nullable();

            $table->text('description')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Purchase Information
            |--------------------------------------------------------------------------
            */
            $table->date('purchase_date')->nullable();

            $table->decimal('purchase_price', 15, 2)
                ->nullable();

            $table->string('purchase_invoice', 100)
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Depreciation
            |--------------------------------------------------------------------------
            */
            $table->string('depreciation_method', 50)
                ->nullable();

            // Umur manfaat dalam tahun
            $table->unsignedInteger('useful_life')
                ->nullable();

            // Nilai residu / salvage value
            $table->decimal('residual_value', 15, 2)
                ->default(0);

            $table->date('depreciation_start_date')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Warranty
            |--------------------------------------------------------------------------
            */
            $table->date('warranty_start')
                ->nullable();

            $table->date('warranty_end')
                ->nullable();

            $table->text('warranty_note')
                ->nullable();


            /*
            /*
            |--------------------------------------------------------------------------
            | Attachments
            |--------------------------------------------------------------------------
            */

            // Maksimal 3 gambar
            $table->json('images')
                ->nullable();

            // Maksimal 5 dokumen invoice
            $table->json('invoice_documents')
                ->nullable();

                /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            |
            | Untuk sementara cukup text.
            | Nanti kalau diperlukan bisa dibuat master locations.
            |
            */
            $table->string('location', 255)
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | 1 = Active
            | 0 = Inactive
            |
            */
            $table->tinyInteger('status')
                ->default(1);


            /*
            |--------------------------------------------------------------------------
            | QR Code
            |--------------------------------------------------------------------------
            */
            $table->string('qr_token', 100)
                ->unique();

            $table->timestamp('qr_generated_at')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Timestamps & Soft Delete
            |--------------------------------------------------------------------------
            */
            $table->timestamps();
            $table->softDeletes();


            /*
            |--------------------------------------------------------------------------
            | Unique Asset Code per Company
            |--------------------------------------------------------------------------
            */
            $table->unique([
                'company_id',
                'asset_code'
            ]);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};