<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formula_variables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formula_id');
            $table->string('variable_name', 100);
            $table->text('expression');
            $table->enum('variable_type', ['input', 'calculated'])->default('calculated');
            $table->json('depends_on')->nullable();
            $table->unsignedInteger('execution_order')->default(0);
            $table->timestamps();

            $table->foreign('formula_id')->references('id')->on('commission_formulas')->cascadeOnDelete();

            $table->index(['formula_id', 'execution_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formula_variables');
    }
};
