<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;


class FortifyServiceProvider extends ServiceProvider
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
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(function () {
            return view('auth.login'); // Vista de login de Laravel UI
        });

        Fortify::registerView(function () {
            return view('auth.register'); // Vista de registro de Laravel UI
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password'); // Vista de olvidé mi contraseña de Laravel UI
        });

        Fortify::resetPasswordView(function ($token) {
            return view('auth.reset-password', ['token' => $token]); // Vista de restablecer contraseña de Laravel UI
        });

        Route::post('/login', [LoginController::class, 'store'])
            ->middleware(['guest'])
            ->name('login');

        Route::post('/logout', [LoginController::class, 'destroy'])
            ->name('logout');


        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
