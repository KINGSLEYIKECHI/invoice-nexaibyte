<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('invoices:overdue')->dailyAt('08:00')->timezone('Africa/Lagos')->withoutOverlapping();
\Illuminate\Support\Facades\Artisan::command('platform:admin {email} {--revoke}',function(){
 $user=\App\Models\User::where('email',$this->argument('email'))->first();
 if(!$user){$this->error('No registered account has that email.');return 1;}
 $user->forceFill(['is_platform_admin'=>!$this->option('revoke')])->save();
 $this->info($this->option('revoke')?'Platform access revoked.':'Platform access enabled. Sign out and sign in again.');
})->purpose('Grant or revoke platform branding access for an existing account');

\Illuminate\Support\Facades\Artisan::command('platform:prune-activity {--days=90}',function(){
 $days=(int)$this->option('days');if($days<7){$this->error('Keep at least seven days.');return 1;}
 $count=\App\Models\ActivityLog::where('created_at','<',now()->subDays($days))->delete();$this->info("Pruned {$count} activity records.");
})->purpose('Remove activity metadata older than the retention period');
Schedule::command('platform:prune-activity')->daily()->withoutOverlapping();
