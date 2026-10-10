<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('businesses',function(Blueprint $t){$t->string('quotation_prefix',15)->default('QUO');$t->string('invoice_separator',1)->default('-');$t->string('quotation_separator',1)->default('-');$t->unsignedTinyInteger('invoice_padding')->default(4);$t->unsignedTinyInteger('quotation_padding')->default(4);});foreach(['invoices','commercial_documents'] as $name)Schema::table($name,fn(Blueprint $t)=>$t->string('po_number',100)->nullable());}
 public function down():void {foreach(['invoices','commercial_documents'] as $name)Schema::table($name,fn(Blueprint $t)=>$t->dropColumn('po_number'));Schema::table('businesses',fn(Blueprint $t)=>$t->dropColumn(['quotation_prefix','invoice_separator','quotation_separator','invoice_padding','quotation_padding']));}
};
