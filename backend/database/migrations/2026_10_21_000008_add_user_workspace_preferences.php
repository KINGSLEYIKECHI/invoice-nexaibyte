<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('users',function(Blueprint $t){$t->string('workspace_mode',10)->default('advanced');$t->json('enabled_modules')->nullable();});}
 public function down():void {Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['workspace_mode','enabled_modules']));}
};
