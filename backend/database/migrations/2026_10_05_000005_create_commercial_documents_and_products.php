<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('invoices',function(Blueprint $t){$t->string('currency',3)->default('NGN');$t->unsignedTinyInteger('currency_minor_units')->default(2);});
  Schema::table('invoice_items',fn(Blueprint $t)=>$t->string('unit',30)->default('pcs'));
  Schema::table('businesses',function(Blueprint $t){$t->unsignedInteger('next_quotation_number')->default(1);$t->unsignedInteger('next_delivery_number')->default(1);});
  Schema::create('products',function(Blueprint $t){$t->id();$t->foreignId('business_id')->constrained()->cascadeOnDelete();$t->string('sku',80);$t->string('name',150);$t->text('description')->nullable();$t->string('unit',30)->default('pcs');$t->unsignedBigInteger('unit_price_minor');$t->string('currency',3);$t->boolean('is_active')->default(true);$t->timestamps();$t->unique(['business_id','sku']);});
  Schema::create('commercial_documents',function(Blueprint $t){$t->id();$t->foreignId('business_id')->constrained()->cascadeOnDelete();$t->foreignId('client_id')->constrained();$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();$t->string('kind',30);$t->string('number');$t->string('status',20)->default('draft');$t->string('currency',3);$t->unsignedTinyInteger('currency_minor_units');$t->date('issue_date');$t->date('due_date');foreach(['subtotal_kobo','discount_kobo','tax_kobo','total_kobo'] as $f)$t->unsignedBigInteger($f)->default(0);$t->decimal('tax_percent',5,2)->default(0);$t->text('notes')->nullable();$t->text('terms')->nullable();$t->text('delivery_address')->nullable();$t->string('received_by',150)->nullable();$t->timestamp('delivered_at')->nullable();$t->timestamps();$t->unique(['business_id','kind','number']);$t->index(['business_id','kind','status']);});
  Schema::create('commercial_document_items',function(Blueprint $t){$t->id();$t->foreignId('business_id')->constrained()->cascadeOnDelete();$t->foreignId('commercial_document_id')->constrained()->cascadeOnDelete();$t->string('description');$t->string('unit',30)->default('pcs');$t->decimal('quantity',10,2);$t->unsignedBigInteger('unit_price_kobo')->default(0);$t->unsignedBigInteger('line_total_kobo')->default(0);$t->unsignedInteger('position');});
 }
 public function down():void {Schema::dropIfExists('commercial_document_items');Schema::dropIfExists('commercial_documents');Schema::dropIfExists('products');Schema::table('businesses',fn(Blueprint $t)=>$t->dropColumn(['next_quotation_number','next_delivery_number']));Schema::table('invoice_items',fn(Blueprint $t)=>$t->dropColumn('unit'));Schema::table('invoices',fn(Blueprint $t)=>$t->dropColumn(['currency','currency_minor_units']));}
};
