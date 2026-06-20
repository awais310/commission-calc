<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_formulas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->text('expression');
            $table->json('variables_used');
            $table->boolean('is_active')->default(false);
            $table->enum('status', ['draft', 'validated', 'active', 'archived'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index('is_active');
            $table->index('status');
            $table->unique('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_formulas');
    }
};
