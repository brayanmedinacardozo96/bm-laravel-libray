<?php

namespace BMCLibrary\Providers;

use BMCLibrary\Contracts\ApiResponseInterface;
use Illuminate\Support\ServiceProvider;
use BMCLibrary\Utils\ApiResponse;

class ConfigManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar ApiResponse como singleton
        $this->app->singleton(ApiResponseInterface::class, ApiResponse::class);
        $this->app->singleton('laravel-utils.api-response', ApiResponse::class);
    }

    public function boot(): void
    {
        // Aquí puedes agregar configuraciones si las necesitas en el futuro
    }
}
