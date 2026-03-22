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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_periodic')->default(false);
            $table->string('periodic_type')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->unsignedTinyInteger('month_of_year')->nullable();
            $table->date('start_date')->nullable();
            $table->timestamps();

            $table->index('date');
            $table->index('due_date');
            $table->index('description');
            $table->index('start_date');
            $table->index(['is_periodic', 'periodic_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
