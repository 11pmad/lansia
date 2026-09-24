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
        Schema::create('ocr_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_report_id')->nullable()->constrained('monthly_reports')->cascadeOnDelete();
            $table->foreignId('kelurahan_id')->nullable()->constrained('kelurahans')->nullOnDelete();
            $table->string('section');
            $table->string('image_path');
            $table->enum('status', ['queued', 'processing', 'done', 'failed', 'applied'])->default('queued');
            $table->json('result_json')->nullable();
            $table->json('confidence_json')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocr_uploads');
    }
};
