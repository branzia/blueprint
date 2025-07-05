<?php 

namespace Branzia\Blueprint\Console;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
class InstallBranzia extends Command{


    protected $signature = 'branzia:install';
    protected $description = '🔧 Install Branzia: publish config, migrate, and seed';
    public function handle(): int
    {
        $this->info('🚀 Starting Branzia installation...');

        // Step 1: Publish vendor assets
        $modulesPath = base_path('packages/Branzia');
        $modules = collect(File::directories($modulesPath))
            ->map(fn($dir) => basename($dir))
            ->filter(fn($name) => File::exists("$modulesPath/{$name}/src/{$name}ServiceProvider.php"))
            ->values();

        foreach ($modules as $module) {
            $tag = strtolower($module) . '-config';
            $this->info("📦 Publishing config for module: {$module}");
            Artisan::call('vendor:publish', ['--tag' => $tag, '--force' => true]);
            $this->line(Artisan::output());
        }

        // Step 2: Run all migrations
        $this->info('📂 Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());
        
        // Step 3: Run master seeder
        foreach ($modules as $module) {
            $module = ucfirst($module);
            $seederClass = "Branzia\\{$module}\\Database\\Seeders\\DatabaseSeeder";
            if (class_exists($seederClass)) {
                $this->info("🌱 Seeding: {$seederClass}");
                Artisan::call('db:seed', [
                    '--class' => $seederClass,
                    '--force' => true,
                ]);
                $this->line(Artisan::output());
            }
        }

        $this->info('✅ Branzia installation completed successfully!');
        return Command::SUCCESS;
    }
}