<?php

namespace BMCLibrary\Providers;

use BMCLibrary\Contracts\ApiResponseInterface;
use BMCLibrary\Contracts\MediatorInterface;
use BMCLibrary\Mediator\Mediator;
use Illuminate\Support\ServiceProvider;
use BMCLibrary\Utils\ApiResponse;

class ConfigManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar ApiResponse como singleton
        $this->app->singleton(ApiResponseInterface::class, ApiResponse::class);
        $this->app->singleton('bm-library.api-response', ApiResponse::class);

        // Registrar Mediator como singleton
        $this->app->singleton(MediatorInterface::class, Mediator::class);
        $this->app->singleton('bm-library.mediator', Mediator::class);
    }

    public function boot(): void
    {
        // Aquí puedes agregar configuraciones si las necesitas en el futuro
    }
}
