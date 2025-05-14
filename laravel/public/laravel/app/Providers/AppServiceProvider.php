<?php

namespace App\Providers;

use App\Models\Payment; // Import the Payment model
use App\Observers\PaymentObserver;
use Illuminate\Support\ServiceProvider;
use Filament\Tables\Table;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Payment::observe(PaymentObserver::class); // Now uses the imported Payment class
        \App\Models\Loan::observe(\App\Observers\LoanObserver::class);
        Table::configureUsing(function (Table $table) {
            $table
                ->paginated([5])
                ->paginationPageOptions([5]);
        });
    }
}