<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Liste des modules actifs
     */
    protected array $modules = [
        'Core',
        'Frontend',
        'Auth',
        'Boutique',
        'Showroom',
        'Atelier',
        'ERP',
        'CRM',
        'HR',
        'Accounting',
        'Reporting',
        'Social',
        'Brand',
        'Assistant',
        'Analytics',
        'CMS',
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Enregistrement des services des modules si nécessaire
        foreach ($this->modules as $module) {
            $configPath = base_path("modules/{$module}/config");
            if (File::isDirectory($configPath)) {
                foreach (File::files($configPath) as $file) {
                    $this->mergeConfigFrom(
                        $file->getPathname(),
                        strtolower($module) . '.' . $file->getFilenameWithoutExtension()
                    );
                }
            }
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadModuleRoutes();
        $this->loadModuleViews();
        $this->loadModuleMigrations();
    }

    /**
     * Charge les routes de chaque module
     */
    protected function loadModuleRoutes(): void
    {
        foreach ($this->modules as $module) {
            $webRoutesPath = base_path("modules/{$module}/routes/web.php");
            
            if (File::exists($webRoutesPath)) {
                Route::middleware('web')
                    ->group($webRoutesPath);
            }

            // Support pour les routes API si nécessaire
            $apiRoutesPath = base_path("modules/{$module}/routes/api.php");
            
            if (File::exists($apiRoutesPath)) {
                Route::prefix('api')
                    ->middleware(['api'])
                    ->group($apiRoutesPath);
            }
        }
    }

    /**
     * Charge les vues de chaque module avec namespace
     */
    protected function loadModuleViews(): void
    {
        foreach ($this->modules as $module) {
            $viewsPath = base_path("modules/{$module}/Resources/views");
            
            if (File::isDirectory($viewsPath)) {
                View::addNamespace(
                    strtolower($module),
                    $viewsPath
                );
            }
        }
    }

    /**
     * Charge les migrations de chaque module
     */
    protected function loadModuleMigrations(): void
    {
        foreach ($this->modules as $module) {
            $migrationsPath = base_path("modules/{$module}/database/migrations");
            
            if (File::isDirectory($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }
        }
    }

    /**
     * Obtenir la liste des modules actifs
     */
    public function getActiveModules(): array
    {
        return $this->modules;
    }
}
