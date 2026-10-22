<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_document_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->unique()->constrained()->cascadeOnDelete();
            $t->longText('definition');
            $t->timestamps();
        });
        Schema::create('business_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('template', 60);
            $t->longText('content');
            $t->timestamps();
            $t->index(['business_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_documents');
        Schema::dropIfExists('business_document_profiles');
    }
};
