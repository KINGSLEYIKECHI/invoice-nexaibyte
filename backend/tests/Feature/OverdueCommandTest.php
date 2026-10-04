<?php
use App\Models\{Invoice,MessageLog};
use App\Jobs\SendOverdueReminder;
use Illuminate\Support\Facades\Queue;
it('marks unpaid past due invoices and schedules each reminder once across tenants',function(){
    $this->seed();Queue::fake();$sent=Invoice::withoutGlobalScopes()->where('status','sent')->first();$sent->update(['due_date'=>today()->subDay()]);
    $other=Invoice::withoutGlobalScopes()->where('business_id',demoUser('owner@kemtech.test')->business_id)->first();$other->update(['due_date'=>today()->addDays(3)]);
    $this->artisan('invoices:overdue')->assertSuccessful();$this->artisan('invoices:overdue')->assertSuccessful();
    expect($sent->fresh()->status)->toBe('overdue')->and($other->fresh()->status)->toBe('sent');
    expect(MessageLog::withoutGlobalScopes()->count())->toBe(4);Queue::assertPushed(SendOverdueReminder::class,4);
    expect(Invoice::withoutGlobalScopes()->where('status','draft')->count())->toBe(1)->and(Invoice::withoutGlobalScopes()->where('status','paid')->count())->toBe(1);
});