<?php

namespace App\Providers;

use App\Support\Money;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
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
        // WAMP's MySQL 5.6 index key limit (767 bytes) with utf8mb4.
        Schema::defaultStringLength(191);

        // @money($amount) prints a currency amount, whole pesos unless the
        // figure genuinely carries cents. @ordinal($n) prints a two-digit
        // index, so menu entries and plate numbers align.
        Blade::directive('money', fn ($expression) => '<?php echo \\'.Money::class."::format($expression); ?>");
        Blade::directive('ordinal', fn ($expression) => "<?php echo str_pad((string) ($expression), 2, '0', STR_PAD_LEFT); ?>");
    }
}
