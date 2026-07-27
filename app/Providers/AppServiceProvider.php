<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\{Product, Rental, Payment};
use App\Observers\{ProductObserver, RentalObserver, PaymentObserver};

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        Rental::observe(RentalObserver::class);
        Payment::observe(PaymentObserver::class);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
    }
}
