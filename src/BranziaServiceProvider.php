<?php

namespace Branzia\Blueprint;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Filament\Forms\Form;
use Branzia\Bootstrap\Form\FormExtensionManager;
abstract class BranziaServiceProvider extends ServiceProvider
{
    abstract public function moduleName(): string;
    abstract public function moduleRootPath(): string;
    
    public function boot(): void
    {
        $module = strtolower($this->moduleName());
        $path = $this->moduleRootPath();

        // Load Translations
        $this->loadTranslationsFrom("{$path}/resources/lang", $module);
        $this->loadJsonTranslationsFrom("{$path}/resources/lang");
        // Publish Translations
        $this->publishes(["{$path}/resources/lang" => resource_path("lang/vendor/{$module}"),], "{$module}-lang");

        // Load Migrations
        $migrationsPath = "{$path}/src/Database/Migrations";
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        // Load Web Routes
        $webRouteFile = "{$path}/routes/web.php";
        if (file_exists($webRouteFile)) {
            $this->loadRoutesFrom($webRouteFile);
        }

        // Load API Routes (Default)
        $apiRouteFile = "{$path}/routes/api.php";
        if (file_exists($apiRouteFile)) {
            Route::prefix('api')->middleware('api')->group($apiRouteFile);
        }

        // Load Versioned API Routes (e.g., v1.php, v2.php)
        $apiRoutesDir = "{$path}/routes/api";
        if (is_dir($apiRoutesDir)) {
            foreach (File::files($apiRoutesDir) as $file) {
                $version = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                Route::prefix("api/{$version}")
                    ->middleware('api')
                    ->group($file->getPathname());
            }
        }

        // Load Views (fallback first)
        $this->loadViewsFrom([resource_path("views/{$module}"),"{$path}/resources/views"], $module);

        // Publish Views
        $this->publishes(["{$path}/resources/views" => resource_path("views/{$module}")], "{$module}-views");

        // Publish Config
        $configFile = "{$path}/config/{$module}.php";
        if (file_exists($configFile)) {
            $this->publishes([$configFile => config_path("branzia/{$module}.php")], "{$module}-config");
        }

        if (!method_exists(Form::class, 'withAdditionalField')) {
            Form::macro('withAdditionalField', function (array $baseSchema, string $resourceClass): array {
                /** @var Form $this */
                return FormExtensionManager::apply($baseSchema, $resourceClass);
            });
        }
    }

    public function register(): void
    {
        $module = strtolower($this->moduleName());
        $configFile = "{$this->moduleRootPath()}/config/{$module}.php";

        if (file_exists($configFile)) {
            $this->mergeConfigFrom($configFile, "{$module}-config");
        }
        $this->app->register(BranziaPanelProvider::class);
    }


    public function filamentDiscoveryPaths(): array
    {
        $module = $this->moduleName();
        $path = $this->moduleRootPath();
        return [
            'resources' => [
                ['path' => $path.'/src/Filament/Resources', 'namespace' => "Branzia\\$module\\Filament\\Resources"],
            ],
            'pages' => [
                ['path' => $path.'/src/Filament/Pages', 'namespace' => "Branzia\\$module\\Filament\\Pages"],
            ],
            'clusters' => [
                ['path' => $path.'/src/Filament/Clusters', 'namespace' => "Branzia\\$module\\Filament\\Clusters"],
            ],
            'widgets' => [
                ['path' => $path.'/src/Filament/Widgets', 'namespace' => "Branzia\\$module\\Filament\\Widgets"],
            ],
        ];
    }

    
}
