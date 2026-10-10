<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('businesses',fn(Blueprint $table)=>$table->json('bank_accounts')->nullable());}
 public function down():void {Schema::table('businesses',fn(Blueprint $table)=>$table->dropColumn('bank_accounts'));}
};
