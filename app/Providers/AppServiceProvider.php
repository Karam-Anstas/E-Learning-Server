<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\URL;
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
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $temporarySignedURL = URL::temporarySignedRoute(
                "verification.verify",
                now()->addMinutes(60),
                [
                    "id" => $notifiable->getKey(),
                    "hash" => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return "http://localhost:5174/verify-email?url=" . urlencode($temporarySignedURL);
        });

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:5174'); // fallback to localhost

            return "http://localhost:5174/reset-password?token={$token}&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });

        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
