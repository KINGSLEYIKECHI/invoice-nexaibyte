<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\TeamPolicy;
class AppServiceProvider extends ServiceProvider {
    public function register(): void {}
    public function boot(): void { Gate::policy(User::class,TeamPolicy::class); }
}