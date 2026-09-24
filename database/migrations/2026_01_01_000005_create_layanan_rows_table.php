<?php

use App\Support\LansiaFields;
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
        Schema::create('layanan_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_report_id')->constrained('monthly_reports')->cascadeOnDelete();
            $table->foreignId('kelurahan_id')->constrained('kelurahans')->restrictOnDelete();

            // Kolom berulang dibuat dengan loop dari LansiaFields (Aturan 04_AGENT_RULES.md)
            foreach (LansiaFields::allLayananRowColumns() as $column) {
                if ($column === 'keterangan') {
                    $table->text('keterangan')->nullable();
                } else {
                    $table->unsignedInteger($column)->nullable();
                }
            }

            $table->timestamps();

            $table->unique(['monthly_report_id', 'kelurahan_id']);
            $table->index('monthly_report_id');
            $table->index('kelurahan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layanan_rows');
    }
};
