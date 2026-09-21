<?php

namespace App\Providers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Observers\StudentObserver;
use App\Observers\TeacherObserver;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        User::observe(UserObserver::class);
        Teacher::observe(TeacherObserver::class);
        Student::observe(StudentObserver::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->string('login').'|'.$request->ip());
        });
    }
}
