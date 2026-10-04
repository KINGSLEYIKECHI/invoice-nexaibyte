<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('integration_settings',function(Blueprint $t){$t->id();$t->text('settings');$t->timestamps();});
  Schema::table('users',fn(Blueprint $t)=>$t->timestamp('last_seen_at')->nullable()->index());
  Schema::create('activity_logs',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('business_id')->nullable()->constrained()->nullOnDelete();$t->string('event',160);$t->unsignedSmallInteger('status_code')->nullable();$t->timestamp('created_at')->index();$t->index(['event','created_at']);$t->index(['user_id','created_at']);});
  Schema::create('payment_notifications',function(Blueprint $t){$t->id();$t->foreignId('payment_id')->constrained()->cascadeOnDelete();$t->foreignId('business_id')->constrained()->cascadeOnDelete();$t->string('recipient');$t->string('kind',20);$t->json('details');$t->string('status',20)->default('pending');$t->unsignedInteger('attempts')->default(0);$t->string('error')->nullable();$t->timestamp('sent_at')->nullable();$t->timestamps();$t->unique(['payment_id','recipient']);});
 }
 public function down():void {Schema::dropIfExists('payment_notifications');Schema::dropIfExists('activity_logs');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('last_seen_at'));Schema::dropIfExists('integration_settings');}
};
