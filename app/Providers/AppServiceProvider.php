<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator; // Tambahkan baris ini
use Illuminate\Support\Facades\URL; // Tambahkan baris ini
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 2. Tambahkan kode ini agar mendeteksi HTTPS ngrok
        if (config('app.env') !== 'local' || isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            URL::forceScheme('https');
        }
        // Tambahkan baris ini agar pagination menggunakan gaya Bootstrap
        Paginator::useBootstrapFive();
    }
}