<?php

namespace Branzia\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;



class BlueprintServiceProvider extends BranziaServiceProvider
{
     public function moduleName(): string
    {
        return 'Blueprint';
    }
    public function moduleRootPath():string{
        return dirname(__DIR__);
    }

    public function boot(): void
    {
        parent::boot();
    }

    public function register(): void
    {
        parent::register();
    }
}

