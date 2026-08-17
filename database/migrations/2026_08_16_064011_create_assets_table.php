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

            // Company
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            // Category
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            // Sub Category - Optional
            $table->foreignId('sub_category_id')
                ->nullable()
                ->constrained('sub_categories')
                ->nullOnDelete();

            $table->string('asset_code', 50);
            $table->string('asset_name');
            $table->text('description')->nullable();

            $table->tinyInteger('status')->default(1);

            $table->timestamps();
            $table->softDeletes();

            // Asset code unik dalam satu company
            $table->unique(['company_id', 'asset_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};