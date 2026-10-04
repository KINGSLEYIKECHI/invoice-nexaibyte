<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('businesses', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->string('email')->nullable(); $t->string('phone')->nullable(); $t->text('address')->nullable(); $t->string('logo_path')->nullable(); $t->string('brand_color')->default('#0F766E'); $t->string('currency')->default('NGN'); $t->decimal('default_tax_percent',5,2)->default(7.5); $t->unsignedInteger('next_invoice_number')->default(1); $t->string('invoice_prefix')->default('INV'); $t->text('payment_instructions')->nullable(); $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) { $t->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete(); $t->string('role')->default('staff'); $t->timestamp('last_login_at')->nullable(); });
        Schema::create('clients', function (Blueprint $t) { $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete(); $t->string('name'); $t->string('email')->nullable(); $t->string('phone')->nullable(); $t->string('company')->nullable(); $t->text('address')->nullable(); $t->text('notes')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete(); $t->foreignId('client_id')->constrained(); $t->foreignId('created_by')->constrained('users'); $t->string('number'); $t->string('status')->default('draft'); $t->date('issue_date'); $t->date('due_date');
            foreach (['subtotal_kobo','discount_kobo','tax_kobo','total_kobo','amount_paid_kobo'] as $c) $t->unsignedBigInteger($c)->default(0);
            $t->decimal('tax_percent',5,2)->default(0); $t->text('notes')->nullable(); $t->text('terms')->nullable(); $t->timestamp('sent_at')->nullable(); $t->timestamp('paid_at')->nullable(); $t->uuid('public_token')->unique(); $t->timestamps(); $t->unique(['business_id','number']); $t->index(['business_id','status','due_date']);
        });
        Schema::create('invoice_items', function (Blueprint $t) { $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete(); $t->foreignId('invoice_id')->constrained()->cascadeOnDelete(); $t->string('description'); $t->decimal('quantity',10,2); $t->unsignedBigInteger('unit_price_kobo'); $t->unsignedBigInteger('line_total_kobo'); $t->unsignedInteger('position'); });
        Schema::create('payments', function (Blueprint $t) { $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete(); $t->foreignId('invoice_id')->constrained()->cascadeOnDelete(); $t->unsignedBigInteger('amount_kobo'); $t->string('method'); $t->string('reference')->nullable(); $t->date('paid_on'); $t->foreignId('recorded_by')->constrained('users'); $t->text('notes')->nullable(); $t->timestamps(); });
        Schema::create('message_logs', function (Blueprint $t) { $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete(); $t->foreignId('invoice_id')->constrained()->cascadeOnDelete(); $t->string('channel'); $t->string('recipient'); $t->string('status')->default('queued'); $t->string('provider_message_id')->nullable(); $t->text('error')->nullable(); $t->unsignedInteger('attempts')->default(0); $t->string('reminder_key')->nullable()->unique(); $t->text('message')->nullable(); $t->timestamps(); });
        Schema::create('personal_access_tokens', function (Blueprint $t) { $t->id(); $t->morphs('tokenable'); $t->string('name'); $t->string('token',64)->unique(); $t->text('abilities')->nullable(); $t->timestamp('last_used_at')->nullable(); $t->timestamp('expires_at')->nullable()->index(); $t->timestamps(); });
    }
    public function down(): void { foreach (['personal_access_tokens','message_logs','payments','invoice_items','invoices','clients'] as $table) Schema::dropIfExists($table); Schema::table('users', fn(Blueprint $t)=>$t->dropConstrainedForeignId('business_id')); Schema::table('users', fn(Blueprint $t)=>$t->dropColumn(['role','last_login_at'])); Schema::dropIfExists('businesses'); }
};
