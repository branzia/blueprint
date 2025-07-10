<?php

namespace Branzia\Blueprint\Filament\Pages;

use Filament\Pages\Page;

class Configurations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static string $view = 'branzia.blueprint.filament.pages.configurations';
    
    protected static ?string $title = 'Configuration';

    public $formData = [];

    public function mount(): void
    {
        // Load config values
        $this->formData = DB::table('config_fields')
            ->get()
            ->mapWithKeys(function ($field) {
                $value = DB::table('config_data')->where('path', $field->path)->value('value') ?? $field->default;
                return [$field->path => $value];
            })->toArray();

        $this->form->fill($this->formData);
    }

    protected function getFormSchema(): array
    {
        $fields = DB::table('config_fields')->get();
        $grouped = $fields->groupBy('tab');

        $schema = [];

        foreach ($grouped as $tab => $itemsByTab) {
            $sections = $itemsByTab->groupBy('section');
            foreach ($sections as $section => $itemsBySection) {
                $groups = $itemsBySection->groupBy('group');
                foreach ($groups as $group => $fieldsGroup) {
                    $schema[] = Forms\Components\Section::make("{$tab} / {$section} / {$group}")
                        ->schema(
                            collect($fieldsGroup)->map(function ($field) {
                                $name = $field->path;
                                $label = $field->label;
                                $type = $field->type;

                                return match ($type) {
                                    'text' => Forms\Components\TextInput::make($name)->label($label),
                                    'number' => Forms\Components\TextInput::make($name)->numeric()->label($label),
                                    'textarea' => Forms\Components\Textarea::make($name)->label($label),
                                    default => Forms\Components\TextInput::make($name)->label($label),
                                };
                            })->toArray()
                        );
                }
            }
        }

        return $schema;
    }

    public function submit()
    {
        foreach ($this->form->getState() as $path => $value) {
            DB::table('config_data')->updateOrInsert(
                ['path' => $path],
                ['value' => $value]
            );
        }

        $this->notify('success', 'Configuration saved successfully!');
    }

    public function getConfigurations(){
        $modules = ['catalog', 'customer', 'sales']; // You can make this dynamic

        $configurations = [];

        foreach ($modules as $module) {
            $config = config("$module.configuration");

            if ($config) {
                $configurations[] = $config;
            }
        }

        return $configurations;
    }
}
