<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create((string) config('files.table', 'files'), function (Blueprint $table): void {
            $table->id();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('extension', 20)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64);
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists((string) config('files.table', 'files'));
    }
};
