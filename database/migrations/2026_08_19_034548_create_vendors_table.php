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
        Schema::create('vendors', function (Blueprint $table) {

            $table->id();

            // Company
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Vendor Name
            $table->string('vendor_name');

            // Vendor Code
            $table->string('vendor_code');

            // Address
            $table->text('address')
                ->nullable();

            // Phone
            $table->string('phone', 20)
                ->nullable();

            // Person In Charge
            $table->string('pic_name')
                ->nullable();
            
            $table->string('email')->unique();
            
            // Description
            $table->text('description')
                ->nullable();

            // Status
            $table->tinyInteger('status')
                ->default(1);

            $table->timestamps();

            // Soft Delete
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Unique Vendor Code per Company
            |--------------------------------------------------------------------------
            |
            | Contoh:
            |
            | Company A -> ABC01
            | Company B -> ABC01
            |
            | Boleh karena company berbeda.
            |
            */
            $table->unique([
                'company_id',
                'vendor_code'
            ]);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
