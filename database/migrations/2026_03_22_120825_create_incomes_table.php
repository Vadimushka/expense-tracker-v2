<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('type');
            $table->string('description')->nullable();
            $table->date('date')->nullable();
            $table->boolean('is_periodic')->default(false);
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->timestamps();

            $table->index('date');
            $table->index(['is_periodic', 'type']);
        });

        DB::statement("ALTER TABLE incomes ADD CONSTRAINT incomes_type_check CHECK (type IN ('salary', 'advance'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
