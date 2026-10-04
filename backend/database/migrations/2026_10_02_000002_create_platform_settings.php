<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('users',fn(Blueprint $t)=>$t->boolean('is_platform_admin')->default(false)); Schema::create('platform_settings',function(Blueprint $t){$t->id();$t->json('settings');$t->timestamps();}); }
 public function down(): void { Schema::dropIfExists('platform_settings');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('is_platform_admin')); }
};
