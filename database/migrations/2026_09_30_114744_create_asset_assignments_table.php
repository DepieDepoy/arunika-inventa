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
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();

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
            | User / Pemegang Aset
            |--------------------------------------------------------------------------
            */
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Periode Assignment
            |--------------------------------------------------------------------------
            */
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Assignment Type
            |--------------------------------------------------------------------------
            | initial  = assignment pertama
            | assign   = pemberian aset
            | transfer = perpindahan user
            | return   = pengembalian aset
            */
            $table->string('assignment_type', 30)
                ->default('assign');

            /*
            |--------------------------------------------------------------------------
            | Reason / Notes
            |--------------------------------------------------------------------------
            */
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | User yang melakukan proses assignment
            |--------------------------------------------------------------------------
            */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */
            $table->index(['asset_id', 'end_at']);
            $table->index(['user_id', 'end_at']);
            $table->index('assignment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};