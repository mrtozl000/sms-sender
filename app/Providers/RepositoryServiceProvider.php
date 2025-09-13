<?php

namespace App\Providers;

use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\MessageRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for repository bindings.
 *
 * Registers repository interfaces to their concrete implementations
 * within the application service container.
 *
 * @package App\Providers
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            MessageRepositoryInterface::class,
            MessageRepository::class
        );
    }
}
