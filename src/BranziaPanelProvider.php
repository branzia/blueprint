<?php
namespace Branzia\Blueprint;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Pages;
use Illuminate\Support\ServiceProvider;
use Branzia\Blueprint\Contracts\ProvidesFilamentDiscovery;
use Filament\Http\Middleware\{
    Authenticate, AuthenticateSession, DisableBladeIconComponents,
    DispatchServingFilamentEvent
};
use Illuminate\Cookie\Middleware\{
    AddQueuedCookiesToResponse, EncryptCookies
};
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Branzia\Admin\Filament\Pages\Auth\Login;
class BranziaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $discoveryPaths = $this->collectBranziaDiscoveryPaths();

        $panel->default()
            ->login(Login::class)
            ->colors(['primary' => Color::Amber])
            ->authGuard('admin')
            ->id('admin')
            ->path('admin')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
            /**
             *  Self-contained discovery
             */
            $this->registerDiscovery($panel, $discoveryPaths['resources'], 'discoverResources');
            $this->registerDiscovery($panel, $discoveryPaths['pages'], 'discoverPages');
            $this->registerDiscovery($panel, $discoveryPaths['clusters'], 'discoverClusters');
            $this->registerDiscovery($panel, $discoveryPaths['widgets'], 'discoverWidgets');                
        return $panel;
    }
    protected function collectBranziaDiscoveryPaths(): array
    {
        $result = [
            'resources' => [],
            'pages' => [],
            'clusters' => [],
            'widgets' => [],
        ];
        foreach (app()->getProviders(ServiceProvider::class) as $provider) {
            if ($provider instanceof ProvidesFilamentDiscovery) {
                $paths = $provider->filamentDiscoveryPaths();
                foreach ($paths as $key => $entries) {
                    $result[$key] = array_merge($result[$key], $entries);
                }
            }
        }
        return $result;
    }
    protected function registerDiscovery(Panel $panel, array $paths, string $method): void
    {
        foreach ($paths as $path) {
            $panel->{$method}(
                in: $path['path'],
                for: $path['namespace']
            );
        }
    }  
}
