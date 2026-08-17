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
        Schema::create('companies', function (Blueprint $table) {

            $table->id();

            $table->string('company_name', 255);
            $table->string('company_code', 100)->unique();

            $table->string('logo')->nullable();
            $table->text('address')->nullable();

            $table->tinyInteger('status')
                ->default(1)
                ->comment('1=Aktif, 0=Nonaktif');
            // Subscription
            $table->string('subscription_plan', 50)
                ->default('free')
                ->comment('free, premium, enterprise');

            $table->string('subscription_status', 20)
                ->default('active')
                ->comment('active, expired, suspended');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};