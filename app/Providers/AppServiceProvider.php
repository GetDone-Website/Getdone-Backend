<?php
namespace App\Providers;

use App\Models\GetDonePersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(GetDonePersonalAccessToken::class);
    }
}
