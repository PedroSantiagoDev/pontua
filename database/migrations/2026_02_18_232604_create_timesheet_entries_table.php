<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->string('punch_type');
            $table->time('recorded_at');
            $table->timestamps();

            $table->unique(['employee_id', 'period_id', 'day', 'punch_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_entries');
    }
};
