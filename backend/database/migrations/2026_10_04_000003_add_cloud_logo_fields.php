<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('businesses',function(Blueprint $t){$t->string('logo_cloud_id')->nullable();$t->text('logo_cloud_url')->nullable();});}
 public function down():void {Schema::table('businesses',function(Blueprint $t){$t->dropColumn(['logo_cloud_id','logo_cloud_url']);});}
};
