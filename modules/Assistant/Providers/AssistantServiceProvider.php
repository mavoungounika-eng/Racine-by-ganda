<?php

namespace Modules\Assistant\Providers;

use Illuminate\Support\ServiceProvider;

class AssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/amira.php',
            'assistant.amira'
        );
    }

    public function boot(): void
    {
        //
    }
}
