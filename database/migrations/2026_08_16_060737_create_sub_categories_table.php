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
        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->string('sub_category_name');
            $table->string('sub_category_code');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'company_id',
                'sub_category_code'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_categories');
    }
};
