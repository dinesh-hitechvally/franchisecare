<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

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
        // SMTP login with an OAuth access token instead of a password (Gmail / Microsoft 365 "connect your account")
        Mail::extend('xoauth2', function (array $config) {
            $transport = new EsmtpTransport($config['host'], (int) ($config['port'] ?? 587));
            $transport->setUsername($config['username']);
            $transport->setPassword($config['password']); // the access token
            $transport->setAuthenticators([new XOAuth2Authenticator]);

            return $transport;
        });
    }
}
