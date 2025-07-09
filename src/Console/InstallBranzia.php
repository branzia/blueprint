<?php 

namespace Branzia\Blueprint\Console;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
class InstallBranzia extends Command{


    protected $signature = 'branzia:install {--fresh : Drop all tables and re-run all migrations}';
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
            /*$this->line(Artisan::output());*/
        }

        // Step 2: Run all migrations
        $this->info('📂 Running migrations...');
        if ($this->option('fresh')) {
            $this->warn('⚠️ Running fresh migration...');
            $this->call('migrate:fresh', [
                '--seed' => true,
                '--force' => true,
            ]);
        } else {
            $this->call('migrate', ['--force' => true]);
            /*$this->line(Artisan::output());*/
        }
        
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
                /*$this->line(Artisan::output());*/
            }
        }
        if(class_exists(\Branzia\Admin\Models\Admin::class)){
            $this->info('👤 Creating initial admin user for Branzia');
            $name = $this->ask('Enter name');
            $email = $this->ask('Enter email');
            $password = $this->secret('Enter password');
            if (! $this->confirm('Create user with these details?', true)) {
                $this->warn('⏭️ Admin user creation was skipped.');
            } else {
                \Branzia\Admin\Models\Admin::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
                $this->info('✅ Admin user created successfully.');
            }
        }

        $this->info('✅ Branzia installation completed successfully!');
        return Command::SUCCESS;
    }
}